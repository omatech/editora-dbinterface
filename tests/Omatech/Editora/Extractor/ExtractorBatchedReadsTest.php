<?php

require_once __DIR__ . '/../TestCaseBase.php';

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Logging\Middleware;
use Omatech\Editora\Extractor\Extractor;
use Psr\Log\AbstractLogger;

/**
 * The extractor reads the values, attribute rows and niceurls of a list's instances together, and resolves each
 * attribute in memory; what it returns must stay what one query per attribute returned.
 */
final class ExtractorBatchedReadsTest extends TestCaseBase
{
    private $statements;
    private $counted;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->statements = new class extends AbstractLogger {
            public array $sql = [];

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                if (isset($context['sql'])) {
                    $this->sql[] = $context['sql'];
                }
            }
        };
        $configuration = new Configuration();
        $configuration->setMiddlewares([new Middleware($this->statements)]);
        $this->counted = DriverManager::getConnection($this->conn->getParams(), $configuration);
    }

    protected function tearDown(): void
    {
        $this->counted->close();
        parent::tearDown();
    }

    public function test_values_resolve_per_language_with_what_one_query_per_attribute_returns(): void
    {
        $es = $this->extractor(['lang' => 'es'])->findInstanceById(1);
        self::assertSame('C1', $es['code']);
        self::assertSame('Hola', $es['title']);
        self::assertSame('Cos', $es['body']);
        self::assertSame('line1<br />line2', $es['notes']);
        self::assertArrayHasKey('empty', $es);
        self::assertNull($es['empty']);
        self::assertSame('/uploads/x.jpg', $es['image']);
        self::assertSame($this->valueId(1, 11), $es['image_imgid']);
        self::assertSame(md5('/uploads/x.jpg').'_'.$this->valueId(1, 11), $es['image_imghash']);
        self::assertSame(640, $es['image_width']);
        self::assertSame(480, $es['image_height']);
        self::assertSame(400, $es['image_container_width']);
        self::assertSame('b', $es['kind']);
        self::assertSame('2024-05-06 07:08:09', $es['when']);

        $ca = $this->extractor(['lang' => 'ca'])->findInstanceById(1);
        self::assertSame('Hola ca', $ca['title']);
        self::assertNull($ca['body']);
        self::assertArrayNotHasKey('empty', $ca);

        // Each attribute row equals the row a query of its own returns, types included.
        $extractor = $this->extractor(['lang' => 'es']);
        $rows = $extractor->getInstanceValues(1, 0, null);
        foreach ($rows as $row) {
            if (is_array($row) && isset($row['atri_id'], $row['tag'])) {
                $alone = (new Extractor($this->conn, ['lang' => 'es']))->getValueRowOrNullArray(1, $row['atri_id'], $row['atri_type']);
                self::assertSame($alone['id'], $row['id'], $row['tag']);
                self::assertSame($alone['num_val'], $row['num_val'], $row['tag']);
                self::assertSame($alone['date_val'], $row['date_val'], $row['tag']);
                self::assertSame($alone['img_info'], $row['img_info'], $row['tag']);
            }
        }
    }

    public function test_a_list_reads_values_attributes_and_niceurls_once_and_gives_what_each_instance_gives(): void
    {
        $children = $this->extractor(['lang' => 'es'], true)->findChildrenInstances(1, 'blocks');

        self::assertSame([5, 6], array_column($children['blocks']['instances'], 'id'));
        self::assertSame(1, $this->statementsWith('from omp_values'));
        self::assertSame(1, $this->statementsWith('from omp_niceurl where'));
        self::assertSame(1, $this->statementsWith('from omp_attributes'));
        foreach ($children['blocks']['instances'] as $instance) {
            self::assertSame($this->extractor(['lang' => 'es'])->findInstanceById($instance['id']), $instance);
        }
    }

    public function test_a_long_list_reads_one_hundred_instances_per_query(): void
    {
        for ($id = 100; $id < 350; $id++) {
            $this->instance($id, 3, "block $id");
            $this->value($id, 10, "code $id");
            if ($id % 3 === 0) {
                $this->value($id, 20, "title $id");
            }
        }

        $blocks = $this->extractor(['lang' => 'es'], true)->findInstancesInClass('block', null, ['order' => 'inst_id', 'order_direction' => 'asc']);

        self::assertCount(252, $blocks);
        self::assertSame(3, $this->statementsWith('from omp_values'));
        self::assertSame(3, $this->statementsWith('from omp_niceurl where'));
        self::assertSame(3, $this->statementsWith('from omp_attributes'));
        foreach ([0, 99, 100, 199, 251] as $position) {
            self::assertSame($this->extractor(['lang' => 'es'])->findInstanceById($blocks[$position]['id']), $blocks[$position]);
        }
    }

    public function test_a_pair_with_several_values_keeps_the_row_the_database_returns_first(): void
    {
        $pages = $this->byId($this->extractor(['lang' => 'es'], true)->findInstancesInClass('page'));

        $alone = (new Extractor($this->conn, ['lang' => 'es']))->getValueRowOrNullArray(2, 10, 'S');
        self::assertContains($alone['text_val'], ['first', 'second']);
        self::assertSame($alone['text_val'], $pages[2]['code']);
        self::assertSame(1, $this->statementsWith('limit 1', 'from omp_values'));
    }

    public function test_a_lookup_without_value_takes_its_first_option(): void
    {
        $pages = $this->byId($this->extractor(['lang' => 'es'])->findInstancesInClass('page'));

        self::assertSame('b', $pages[1]['kind']);
        self::assertSame('a', $pages[2]['kind']);
    }

    public function test_unpublished_future_expired_and_deleted_instances_stay_out_unless_previewing(): void
    {
        $published = $this->byId($this->extractor(['lang' => 'es'])->findInstancesInClass('page'));
        $preview = $this->byId($this->extractor(['lang' => 'es', 'preview' => true])->findInstancesInClass('page'));

        self::assertSame([1, 2], array_keys($published));
        self::assertSame([1, 2, 3, 4, 7], array_keys($preview));
        self::assertSame('Draft', $preview[3]['title']);
        self::assertSame([], $this->extractor(['lang' => 'es'])->findInstanceById(3));
        self::assertSame([], $this->extractor(['lang' => 'es'])->findInstanceById(999));
        self::assertSame([5, 6], array_column($this->extractor(['lang' => 'es'])->findChildrenInstances(1, 'blocks')['blocks']['instances'], 'id'));
    }

    public function test_one_extractor_changing_language_or_filter_gives_what_new_extractors_give(): void
    {
        $extractor = $this->extractor(['lang' => 'es', 'metadata' => true]);
        $es = $extractor->findInstancesInClass('page', null, ['order' => 'inst_id', 'order_direction' => 'asc']);
        $extractor->setParams(['lang' => 'ca']);
        $ca = $extractor->findInstancesInClass('page', null, ['order' => 'inst_id', 'order_direction' => 'asc']);
        $extractor->setParams(['lang' => 'es']);
        $fields = $extractor->findInstancesInClass('page', null, ['order' => 'inst_id', 'order_direction' => 'asc', 'filter' => 'fields:title|code']);

        self::assertSame($this->extractor(['lang' => 'es', 'metadata' => true])->findInstancesInClass('page', null, ['order' => 'inst_id', 'order_direction' => 'asc']), $es);
        self::assertSame($this->extractor(['lang' => 'ca', 'metadata' => true])->findInstancesInClass('page', null, ['order' => 'inst_id', 'order_direction' => 'asc']), $ca);
        self::assertSame($this->extractor(['lang' => 'es', 'metadata' => true])->findInstancesInClass('page', null, ['order' => 'inst_id', 'order_direction' => 'asc', 'filter' => 'fields:title|code']), $fields);
        self::assertArrayNotHasKey('body', $fields[0]);

        // A callback may change the language while the list is being extracted: the next instance follows it.
        $switching = $this->extractor(['lang' => 'es']);
        $children = $switching->findChildrenInstances(1, 'blocks', null, null, function () use ($switching) {
            $switching->setParams(['lang' => 'ca']);
            return [];
        })['blocks']['instances'];
        self::assertSame(['es', 'ca'], array_column($children, 'lang'));
        self::assertSame(['Bloc 5', 'Bloc 6 ca'], array_column($children, 'title'));
    }

    public function test_children_and_parents_found_through_callbacks_keep_their_values(): void
    {
        $extractor = $this->extractor(['lang' => 'es']);
        $page = $extractor->findInstanceById(1, null, fn ($i) => $extractor->findChildrenInstances($i, 'blocks', null, null,
            fn ($block) => $extractor->findParentInstances($block, 'blocks')));

        $blocks = $page['relations']['blocks']['instances'];
        self::assertSame(['B5', null], array_column($blocks, 'code'));
        self::assertSame([[1, 2], [1]], array_map(fn ($block) => array_column($block['relations']['blocks']['instances'], 'id'), $blocks));
        self::assertSame('Dos', $blocks[0]['relations']['blocks']['instances'][1]['title']);
    }

    public function test_a_language_without_attributes_reads_only_those_for_all_languages(): void
    {
        $page = $this->extractor(['lang' => null])->findInstanceById(1);

        self::assertSame('C1', $page['code']);
        self::assertArrayNotHasKey('title', $page);
    }

    public function test_without_values_no_value_is_read(): void
    {
        $pages = $this->extractor(['lang' => 'es', 'extract_values' => false], true)->findInstancesInClass('page');

        self::assertCount(2, $pages);
        self::assertSame(0, $this->statementsWith('from omp_values'));
        self::assertSame(0, $this->statementsWith('from omp_attributes'));
    }

    public function test_the_database_name_is_read_only_for_the_debug_trace(): void
    {
        $this->extractor(['lang' => 'es'], true)->findInstanceById(1);
        self::assertSame(0, $this->statementsWith('database()'));

        $debug = $this->extractor(['lang' => 'es', 'debug' => true], true);
        $debug->findInstanceById(1);
        self::assertStringContainsString('MEMCACHE:: using key editora_testing:dbinterface:es:1:all', $debug->debug_messages);
    }

    public function test_links_keep_what_one_query_per_instance_gives(): void
    {
        $pages = $this->byId($this->extractor(['lang' => 'es'])->findInstancesInClass('page'));
        $alone = new Extractor($this->conn, ['lang' => 'es']);

        self::assertSame('/es/hola', $pages[1]['link']);
        self::assertSame($alone->getInstanceLink(2), $pages[2]['link']);
        self::assertContains($pages[2]['link'], ['/es/dos', '/es/dos-bis']);
        self::assertTrue($pages[2]['has_urlnice']);

        $blocks = $this->extractor(['lang' => 'ca'])->findChildrenInstances(1, 'blocks')['blocks']['instances'];
        self::assertSame(["/ca/'5'", "/ca/'6'"], array_column($blocks, 'link'));
        self::assertSame([false, false], array_column($blocks, 'has_urlnice'));
    }

    private function extractor(array $params, bool $counted = false): Extractor
    {
        $this->statements->sql = [];

        return new Extractor($counted ? $this->counted : $this->conn, $params);
    }

    private function statementsWith(string ...$fragments): int
    {
        return count(array_filter($this->statements->sql, function ($sql) use ($fragments) {
            foreach ($fragments as $fragment) {
                if (stripos(preg_replace('/\s+/', ' ', $sql), $fragment) === false) {
                    return false;
                }
            }
            return true;
        }));
    }

    private function byId(array $instances): array
    {
        $byId = array_column($instances, null, 'id');
        ksort($byId);

        return $byId;
    }

    private function valueId(int $inst_id, int $atri_id): int
    {
        return (int) $this->conn->fetchOne("select id from omp_values where inst_id=$inst_id and atri_id=$atri_id");
    }

    private function instance(int $id, int $class_id, string $name, string $status = 'O', string $begins = '2020-01-01 00:00:00', ?string $ends = null): void
    {
        $this->conn->insert('omp_instances', ['id' => $id, 'class_id' => $class_id, 'key_fields' => $name, 'status' => $status,
            'publishing_begins' => $begins, 'publishing_ends' => $ends, 'update_date' => '2024-01-01 00:00:00']);
    }

    private function value(int $inst_id, int $atri_id, ?string $text, array $other = []): void
    {
        $this->conn->insert('omp_values', ['inst_id' => $inst_id, 'atri_id' => $atri_id, 'text_val' => $text] + $other);
    }

    private function seed(): void
    {
        $this->conn->insert('omp_classes', ['id' => 2, 'name' => 'Page', 'tag' => 'page']);
        $this->conn->insert('omp_classes', ['id' => 3, 'name' => 'Block', 'tag' => 'block']);
        $attributes = [
            [10, 'code', 'S', 'ALL'], [11, 'image', 'I', 'ALL'], [12, 'kind', 'L', 'ALL'], [13, 'when', 'D', 'ALL'],
            [20, 'title', 'S', 'es'], [21, 'title', 'S', 'ca'], [22, 'body', 'K', 'es'], [23, 'body', 'K', 'ca'], [24, 'empty', 'S', 'es'],
            // An A attribute without value gives '' and a PHP deprecation (str_replace() of null): every page has one.
            [25, 'notes', 'A', 'ALL'],
        ];
        foreach ($attributes as [$id, $tag, $type, $language]) {
            $this->conn->insert('omp_attributes', ['id' => $id, 'name' => "{$tag}_{$language}", 'tag' => $tag, 'type' => $type, 'language' => $language,
                'lookup_id' => $type === 'L' ? 1 : null, 'img_width' => $type === 'I' ? 400 : null, 'img_height' => $type === 'I' ? 300 : null]);
        }
        // Attribute 20 twice in the page class, as some Editora databases have it.
        foreach ([[2, 10], [2, 11], [2, 12], [2, 13], [2, 20], [2, 20], [2, 21], [2, 22], [2, 23], [2, 24], [2, 25], [3, 10], [3, 20], [3, 21]] as [$class_id, $atri_id]) {
            $this->conn->insert('omp_class_attributes', ['class_id' => $class_id, 'atri_id' => $atri_id]);
        }
        $this->conn->insert('omp_lookups', ['id' => 1, 'name' => 'kinds']);
        $this->conn->insert('omp_lookups_values', ['id' => 100, 'lookup_id' => 1, 'ordre' => 2, 'value' => 'b']);
        $this->conn->insert('omp_lookups_values', ['id' => 101, 'lookup_id' => 1, 'ordre' => 1, 'value' => 'a']);

        $this->instance(1, 2, 'one');
        $this->instance(2, 2, 'two');
        $this->instance(3, 2, 'draft', 'P');
        $this->instance(4, 2, 'future', 'O', '2099-01-01 00:00:00');
        $this->instance(5, 3, 'b5');
        $this->instance(6, 3, 'b6');
        $this->instance(7, 2, 'expired', 'O', '2020-01-01 00:00:00', '2021-01-01 00:00:00');

        $this->value(1, 10, 'C1');
        $this->value(1, 11, 'uploads/x.jpg', ['img_info' => '640.480']);
        $this->value(1, 12, null, ['num_val' => 100]);
        $this->value(1, 13, null, ['date_val' => '2024-05-06 07:08:09']);
        $this->value(1, 20, 'Hola');
        $this->value(1, 21, 'Hola ca');
        $this->value(1, 22, 'Cos');
        foreach ([1 => "line1\nline2", 2 => 'n2', 3 => 'n3', 4 => 'n4', 7 => 'n7'] as $inst_id => $notes) {
            $this->value($inst_id, 25, $notes);
        }
        $this->value(1, 99, 'no such attribute');
        $this->value(2, 10, 'first');
        $this->value(2, 10, 'second');
        $this->value(2, 20, 'Dos');
        $this->value(3, 20, 'Draft');
        $this->value(5, 10, 'B5');
        $this->value(5, 20, 'Bloc 5');
        $this->value(6, 21, 'Bloc 6 ca');

        foreach ([[1, 'es', 'hola'], [1, 'ca', 'hola-ca'], [2, 'es', 'dos'], [2, 'es', 'dos-bis']] as [$inst_id, $language, $niceurl]) {
            $this->conn->insert('omp_niceurl', ['inst_id' => $inst_id, 'language' => $language, 'niceurl' => $niceurl]);
        }
        $this->conn->insert('omp_relations', ['id' => 50, 'name' => 'blocks', 'tag' => 'blocks', 'parent_class_id' => 2, 'child_class_id' => 3]);
        foreach ([[1, 5, 1], [1, 6, 2], [1, 999, 3], [2, 5, 2]] as [$parent, $child, $weight]) {
            $this->conn->insert('omp_relation_instances', ['rel_id' => 50, 'parent_inst_id' => $parent, 'child_inst_id' => $child, 'weight' => $weight]);
        }
    }
}
