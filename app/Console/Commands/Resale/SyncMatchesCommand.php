<?php

namespace App\Console\Commands\Resale;

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

        if ($this->option('matches')) {
            $this->syncMatches();

            return;
        }

        if ($this->option('categories')) {
            $this->syncCategories(false);

            return;
        }

        while (true) {
            $response = $this->client->get('selection/event/date/product/10229225515651/contact-advantages/10229516236677,10229516236679/lang/en');
            $content  = $response->getBody()->getContents();

            if (str_contains($content, '<title>Performance selection')) {
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
        $this->syncCategories(true);
    }

    private function syncMatches(string $content)
    {
        $crawler = new Crawler();
        $crawler->addHtmlContent($content);

        $matches = [];
        $crawler->filter('.performance')->each(function (Crawler $node) use (&$matches) {
            $id        = $node->attr('id');
            $venueId   = $node->attr('data-venue-id');
            $venueName = $node->filter('span.site')->attr('title');
            $date      = $node->filter('.date_time_container')->text();
            $code      = $node->filter('.match_round_code')->text();
            $stage     = $node->ancestors()->ancestors()->ancestors()->ancestors()->filter('div > div > h3')->text();

            $matches[] = [
                'id'         => $id,
                'venue_id'   => $venueId,
                'venue_name' => $venueName,
                'date'       => Carbon::createFromFormat('D j M H:i', $date),
                'code'       => $code,
                'stage'      => $stage
            ];
        });

        foreach ($matches as $match) {
            MatchObject::updateOrCreate(['id' => $match['id']], Arr::except($match, ['id']));
        }
    }

    private function syncCategories()
    {
        $matches = MatchObject::all();

        foreach ($matches as $match) {
            try {
                $response = $this->client->get('selection/event/seat/performance/' . $match['id'] . '/contact-advantages/10229516236677,10229516236679/lang/en');
                $content  = $response->getBody()->getContents();
            } catch (RequestException $e) {
                $this->logger->error('Failed to read ' . $match['id'] . ' product page: ' . $e->getMessage());

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

                if (!$recent || $recent->price_min != $category['price_min'] || $recent->price_max != $category['price_max']) {
                    MatchCategoryUpdate::create([
                        'match_id'      => $match->id,
                        'category_id'   => $category['id'],
                        'category_name' => $category['name'],
                        'price_min'     => $category['price_min'],
                        'price_max'     => $category['price_max']
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
