<?php declare(strict_types=1);

namespace Dbl\Tests;

use Dbl\{Database, Exception};
use Dbl\Tests\Support\TestCache;
use PHPUnit\Framework\TestCase;
use Pseudo\Pdo;

class DatabaseTest extends TestCase
{
    protected $pdo;

    protected $db;

    protected function setUp(): void
    {
        $this->pdo = new Pdo;

        $settings = [
            'connections' => ['default' => $this->pdo],
            'fetch_mode' => \PDO::FETCH_ASSOC,
            'foo' => 'bar'
        ];

        $this->db = new Database($settings);
    }

    public function testConstructor(): void
    {
        $this->assertEquals(\PDO::FETCH_ASSOC, $this->db->settings['fetch_mode']);
        $this->assertEquals('bar', $this->db->settings['foo']);
        $this->assertEquals('___', $this->db->settings['related_data_separator']);
    }

    public function testGetInstance(): void
    {
        $this->assertInstanceOf(Database::class, Database::getInstance());
    }

    public function testGetPDO(): void
    {
        $this->assertInstanceOf(\PDO::class, $this->db->getPDO());

        $this->expectException(Exception::class);

        $this->db->getPDO('inexistent');
    }

    public function testFetch(): void
    {
        $pdo = $this->db->getPDO();
        $query = 'SELECT * FROM users';

        $results = [
            ['id' => 1, 'name' => 'John Doe'],
            ['id' => 2, 'name' => 'Arnold Munich'],
        ];

        $pdo->mock($query, $results);

        foreach ($this->db->fetch($query) as $k => $record) {
            $this->assertInstanceOf(\Dbl\Collection::class, $record);
            $this->assertEquals($results[$k]['id'], $record->id);
            $this->assertEquals($results[$k]['name'], $record->name);
        }
    }

    public function testFetchAll(): void
    {
        $pdo = $this->db->getPDO();
        $query = 'SELECT * FROM users';

        $results = [
            ['id' => 1, 'name' => 'John Doe'],
            ['id' => 2, 'name' => 'Arnold Munich'],
        ];

        $pdo->mock($query, $results);

        $results = $this->db->fetchAll($query);

        $this->assertInstanceOf(\Dbl\Collection::class, $results);

        foreach ($results as $k => $record) {
            $this->assertInstanceOf(\Dbl\Collection::class, $record);
            $this->assertEquals($results[$k]['id'], $record->id);
            $this->assertEquals($results[$k]['name'], $record->name);
        }
    }

    public function testSingle(): void
    {
        $pdo = $this->db->getPDO();
        $query = 'SELECT id FROM users LIMIT 1';

        $results = [
            ['id' => 1, 'name' => 'John Doe'],
            ['id' => 2, 'name' => 'Arnold Munich'],
        ];

        $pdo->mock($query, $results);

        $id = $this->db->single($query);

        $this->assertEquals(1, $id);
    }

    public function testExecute(): void
    {
        $pdo = $this->db->getPDO();
        $result = new \Pseudo\Result;
        $query = 'INSERT INTO users VALUES(1, "John Doe")';

        $result->setInsertId(1);
        $result->setAffectedRowCount(1);
        $pdo->mock($query, $result);

        $summary = $this->db->execute($query);

        $this->assertEquals(1, $summary->lastInsertId);
        $this->assertEquals(1, $summary->rowCount);
    }

    public function testCache(): void
    {
        $value = $this->db->cache('test', 60, function () {
            return 'Test!';
        });

        $this->assertEquals('Test!', $value);

        $this->db = new Database([
            'connections' => ['default' => $this->pdo],
            'cache' => new TestCache,
        ]);

        $value = $this->db->cache('test', 60, function () {
            return 'Test!';
        });

        $this->assertEquals('Test! #DBL', $value);

        $tmpCache = new class {
            public function remember($key, $ttl, $callback)
            {
                return $callback();
            }
        };

        $this->db = new Database([
            'connections' => ['default' => $this->pdo],
            'cache' => $tmpCache,
        ]);

        $this->expectException(Exception::class);

        $this->db->cache('test', 60, function () {
            return 'Test!';
        });
    }
}