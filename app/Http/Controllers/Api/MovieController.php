<?php

namespace App\Http\Controllers\Api;

use App\Clients\TmdbClient;
use App\Enums\MovieCompanyRole;
use App\Enums\MovieExternalId as EnumsMovieExternalId;
use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Models\MovieExternalId;
use App\Services\MovieService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use MrGarest\EchoApi\EchoApi;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;

class MovieController extends Controller
{
    /**
     * Search for movies by title.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string',
            'page' => 'nullable|integer',
            'year' => 'nullable|integer'
        ]);

        if ($validator->fails()) return EchoApi::httpError(Response::HTTP_BAD_REQUEST);
        $data = $validator->getData();
        $page = $data['page'] ?? 1;
        $year = $data['year'] ?? null;
        $query = trim(urldecode($data['q']));

        $cacheKey = 'movie_search_raw_' . md5($query) . '_' . $page . ($year != null ? '_' . $year : '');
        $DATA = Cache::get($cacheKey, null);

        if ($DATA == null) {
            $searchMovie = TmdbClient::searchMovie($query, 'uk-UA', $page, $year);
            if ($searchMovie == null || empty($searchMovie['results'])) return EchoApi::success(['items' => []]);

            $DATA['items'] = [];
            $tmdbIds = [];

            foreach ($searchMovie['results'] as $value) {
                if (in_array($value['original_language'], [
                    'ru',
                    'by'
                ]) || !isset($value['release_date'])) continue;
                $DATA['items'][] = [
                    'id' => null,
                    'release_year' => Carbon::parse($value['release_date'])->year,
                    'tmdb_id' => $value['id'],
                    'title' => $value['title'],
                    'original_title' => $value['original_title'],
                    'poster_url' => TmdbClient::getImageUrl('w200', $value['poster_path'])
                ];
            }
        }

        if (!empty($DATA['items'])) {
            $tmdbIds = array_column($DATA['items'], 'tmdb_id');

            if (!empty($tmdbIds)) {
                $movieExternalIds = MovieExternalId::externalId(EnumsMovieExternalId::TMDB)
                    ->whereIn('value', $tmdbIds)
                    ->get()
                    ->keyBy('value');

                foreach ($DATA['items'] as $key => $item) {
                    $tmdbId = $item['tmdb_id'];

                    if (isset($movieExternalIds[$tmdbId])) {
                        $DATA['items'][$key]['id'] = $movieExternalIds[$tmdbId]->movie_id;
                    }
                }
            }
        }

        if ($DATA == null) Cache::put($cacheKey, $DATA, Carbon::now()->addMinutes(15));

        return EchoApi::success($DATA);
    }

    public function check(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'nullable|integer',
            'tmdb_id' => 'nullable|integer'
        ]);
        if ($validator->fails()) return EchoApi::httpError(Response::HTTP_BAD_REQUEST);

        $dataValidator = $validator->getData();
        $idValidator = $dataValidator['id'] ?? null;

        $cacheKey = 'movie_check_';
        if ($idValidator) {
            $data = Cache::get($cacheKey . $idValidator, null);
            if ($data) return EchoApi::success($data);
        }

        $withMovie = [
            'companies' => function ($query) {
                $query->with(['company' => function ($query) {
                    $query->select('id', 'name');
                }]);
            },
            'externalIds' => function ($query) {
                $query->where('external_id', EnumsMovieExternalId::IMDB->value);
            },
            'imdbContentRatings'
        ];

        $selectMovie = [
            'id',
            'release_date',
            'rating_imdb'
        ];

        $movie = null;
        $movieId = null;
        if ($idValidator) $movieId = $idValidator;
        elseif (isset($dataValidator['tmdb_id'])) {
            $movieExternalId = MovieExternalId::with(['movie' => function ($query) use ($withMovie, $selectMovie) {
                $query->select($selectMovie)->with($withMovie);
            }])->externalAndValue(EnumsMovieExternalId::TMDB, $dataValidator['tmdb_id'])->first();

            $movie = $movieExternalId->movie ?? null;

            if ($movie) $movieId = $movie->id;
            else {
                $handler = MovieService::handleAddMovie($dataValidator['tmdb_id']);
                $movieId = $handler['movie']->id ?? null;
            }
        } else return EchoApi::httpError(Response::HTTP_BAD_REQUEST);

        if (!$movie || !$movieId) {
            $movie = Movie::with($withMovie)->select($selectMovie)->find($movieId);
        }

        if (!$movie) return EchoApi::httpError(Response::HTTP_NOT_FOUND);

        $productions = $distributors = null;
        if (!$movie->companies->isEmpty()) foreach ($movie->companies as $сompany) {
            $name = $сompany->company->name;
            if (Str::contains(mb_strtolower($name), [
                'warner',
                'disney',
                'netflix',
                'apple',
                'hbo',
                'amazon',
            ])) $hazardLevel = 2;
            elseif (Str::contains(mb_strtolower($name), [
                'marvel'
            ])) $hazardLevel = 3;
            else $hazardLevel = 0;

            $item = [
                'id' => $сompany->company_id,
                'hazard_level' => $hazardLevel,
                'name' => $name,
            ];

            switch ($сompany->role_id) {
                case MovieCompanyRole::PRODUCTION->value:
                    $productions[] = $item;
                    break;
                case MovieCompanyRole::DISTRIBUTOR->value:
                    $distributors[] = $item;
                    break;
                default:
                    continue;
            }
        }

        $imdbId = $movie->externalIds[0]->value ?? null;
        $contentRatings = null;
        if ($imdbId != null && !$movie->imdbContentRatings->isEmpty()) {
            $contentRatings = [];
            foreach ($movie->imdbContentRatings as $rating) $contentRatings[] = [
                'content_id' => $rating->content_id,
                'level' => $rating->level
            ];
        }

        $data = [
            'id' => $movie->id,
            'release' => $movie->release_date != null ? [
                'hazard' => $movie->release_date->greaterThan(Carbon::now()->subYears(4)),
                'release_date' => $movie->release_date->toDateString(),
            ] : null,
            'productions' => $productions,
            'distributors' => $distributors,
            'imdb' => $imdbId ? [
                'id' => $imdbId,
                'content_ratings' => $contentRatings
            ] : null
        ];

        Cache::put($cacheKey . $movie->id, $data, Carbon::now()->addMinutes(10));

        return EchoApi::success($data);
    }
}
