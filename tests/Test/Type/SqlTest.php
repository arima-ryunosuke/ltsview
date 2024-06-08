<?php

namespace ryunosuke\test\Type;

class SqlTest extends AbstractTestCase
{
    function test_comment()
    {
        $type = $this->getType([
            'comment' => true,
            'compact' => false,
            'color'   => false,
            'table'   => 't_hoge',
        ]);
        $fields = ['a' => null, 'b' => 1, 'c' => "hello'world"];
        $buffer = '';
        $buffer .= $type->head(array_keys($fields));
        $buffer .= $type->meta('dummy', 1);
        $buffer .= $type->body($fields);
        $buffer .= $type->meta('dummy', 2);
        $buffer .= $type->body($fields);
        $buffer .= $type->foot();
        $this->assertEquals(<<<SQL
        -- dummy:1
        INSERT INTO t_hoge (a, b, c) VALUES (NULL, 1, 'hello\'world');
        -- dummy:2
        INSERT INTO t_hoge (a, b, c) VALUES (NULL, 1, 'hello\'world');
        
        SQL, $buffer, "Actual:\n$buffer");
    }

    function test_compact()
    {
        $type = $this->getType([
            'comment' => false,
            'compact' => true,
            'color'   => false,
            'table'   => 't_hoge',
        ]);
        $fields = ['a' => null, 'b' => 1, 'c' => "hello'world"];
        $buffer = '';
        $buffer .= $type->head(array_keys($fields));
        $buffer .= $type->meta('dummy', 1);
        $buffer .= $type->body($fields);
        $buffer .= $type->meta('dummy', 2);
        $buffer .= $type->body($fields);
        $buffer .= $type->foot();
        $this->assertEquals(<<<SQL
        INSERT INTO t_hoge (a,b,c) VALUES
        (NULL,1,'hello\'world'),
        (NULL,1,'hello\'world');
        
        SQL, $buffer, "Actual:\n$buffer");
    }

    function test_meta()
    {
        $type = $this->getType([
            'comment' => true,
            'compact' => true,
            'color'   => false,
            'table'   => 't_hoge',
        ]);
        $fields = ['a' => null, 'b' => 1, 'c' => "hello'world"];
        $buffer = '';
        $buffer .= $type->head(array_keys($fields));
        $buffer .= $type->meta('dummy', 1);
        $buffer .= $type->body($fields);
        $buffer .= $type->meta('dummy', 2);
        $buffer .= $type->body($fields);
        $buffer .= $type->foot();
        $this->assertEquals(<<<SQL
        INSERT INTO t_hoge (a,b,c) VALUES
        -- dummy:1
        (NULL,1,'hello\'world'),
        -- dummy:2
        (NULL,1,'hello\'world');
        
        SQL, $buffer, "Actual:\n$buffer");
    }
}
