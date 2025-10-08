<?php

namespace App\Console\Commands\Resale;

use App\Models\MatchCategory;
use App\Models\MatchCategoryUpdate;
use App\Models\MatchObject;
use App\Services\ResaleClient;
use Carbon\Carbon;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DomCrawler\Crawler;

class SyncMatchesCommand extends Command
{
    protected $name = 'resale:matches:sync';

    private ResaleClient $client;
    private LoggerInterface $logger;

    public function handle(ResaleClient $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;

        while (true) {
            $this->logger->debug('Attempting to load product listing page');

            $response = $this->client->get('selection/event/date/product/10229225515651/contact-advantages/10229516236677,10229516236679/lang/en');
            $content  = $response->getBody()->getContents();

            if (str_contains($content, '<title>Performance selection')) {
                $this->logger->debug('Detected valid product listing page');

                $this->sync($content);

                sleep(60);
                continue;
            }

            $this->error('Logged out?');

            return;
        }
    }

    public function sync(string $content)
    {
        $this->syncMatches($content);
        $this->syncCategories();
    }

    private function syncMatches(string $content)
    {
        $crawler = new Crawler();
        $crawler->addHtmlContent($content);

        $matches = [];
        $crawler->filter('.performance')->each(function (Crawler $node) use (&$matches) {
            $id      = $node->attr('id');
            $venueId = $node->attr('data-venue-id');
            $date    = $node->filter('.date_time_container')->text();
            $code    = $node->filter('.match_round_code')->text();
            $stage   = $node->ancestors()->ancestors()->ancestors()->ancestors()->filter('div > div > h3')->text();

            $matches[] = [
                'id'       => $id,
                'venue_id' => $venueId,
                'date'     => Carbon::createFromFormat('D j M H:i', $date),
                'code'     => $code,
                'stage'    => $stage
            ];
        });

        foreach ($matches as $match) {
            $matchObject = MatchObject::find($match['id']);

            if (null === $matchObject) {
                $this->logger->debug('Detected new match ' . $match['id']);
                $matchObject = new MatchObject();
            }

            $matchObject->fill(Arr::except($match, ['id']));

            if ($matchObject->isDirty()) {
                $this->logger->debug('Saved match ' . $matchObject->id);
                $matchObject->save();
            }
        }
    }

    private function syncCategories()
    {
        $matches = MatchObject::all();

        foreach ($matches as $match) {
            $this->logger->debug('Attempting to load match ' . $match->id . ' page');

            try {
                $response = $this->client->get('selection/event/seat/performance/' . $match['id'] . '/contact-advantages/10229516236677,10229516236679/lang/en');
                $content  = $response->getBody()->getContents();
            } catch (RequestException $e) {
                $this->logger->error('Failed to read match ' . $match->id . ' page: ' . $e->getMessage());

                continue;
            }

            $this->syncMatchCategories($match, $content);
        }
    }

    private function syncMatchCategories(MatchObject $match, string $html)
    {
        $crawler = new Crawler();
        $crawler->addHtmlContent($html);

        $crawler->filter('.table_container table tbody tr')->each(function (Crawler $node) use (&$matches, &$categories, &$matchesWithCategories, $match) {
            $id = '';

            if (preg_match('/v2-seatcat_([0-9]+)/', $node->attr('class'), $matches)) {
                $id = $matches[1];
            }

            $product  = $node->filter('th.category')->text('');
            $priceMin = (int) $node->filter('.resale_min')->attr('data-amount', 0);
            $priceMax = (int) $node->filter('.resale_max')->attr('data-amount', 0);

            if ($id && $product && $priceMin && $priceMax) {
                $category = [
                    'id'        => $id,
                    'name'      => $product,
                    'price_min' => round($priceMin / 1000, 2),
                    'price_max' => round($priceMax / 1000, 2)
                ];

                $recent = DB::selectOne("select c.*
                    from (
                        select match_id, category_id, max(id) as max_id
                        from match_category_updates
                        group by match_id, category_id
                    ) l
                    join match_category_updates c on (c.id = l.max_id)
                    where l.match_id = ? and l.category_id = ?", [$match->id, $category['id']]);

                $categoryObject = MatchCategory::find($category['id']);

                if (null === $categoryObject) {
                    $this->logger->debug('Detected new category ' . $category['id']);
                    $categoryObject = new MatchCategory();
                }

                $categoryObject->fill([
                    'id'       => $category['id'],
                    'venue_id' => $match->venue_id,
                    'name'     => $category['name']
                ]);

                if ($categoryObject->isDirty()) {
                    $this->logger->debug('Saved category ' . $categoryObject->id);
                    $categoryObject->save();
                }

                if (!$recent || $recent->price_min != $category['price_min'] || $recent->price_max != $category['price_max']) {
                    $this->logger->error('Detected new price for match ' . $match->id . ', category ' . $category['id']);

                    MatchCategoryUpdate::create([
                        'match_id'    => $match->id,
                        'category_id' => $category['id'],
                        'price_min'   => $category['price_min'],
                        'price_max'   => $category['price_max']
                    ]);
                }
            }
        });
    }

    protected function getOptions()
    {
        return [
            ['matches', null, InputOption::VALUE_NONE],
            ['categories', null, InputOption::VALUE_NONE]
        ];
    }
}
