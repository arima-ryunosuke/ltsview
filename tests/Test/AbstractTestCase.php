<?php

namespace ryunosuke\test;

use PHPUnit\Framework\Error\Error;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\TestCase;

abstract class AbstractTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public static function assertException($e, $callback)
    {
        if (is_string($e)) {
            $e = new \Exception($e);
        }

        $callback = self::forcedCallize($callback);

        try {
            $callback(...array_slice(func_get_args(), 2));
        }
        catch (Error $ex) {
            throw $ex;
        }
        catch (Exception $ex) {
            throw $ex;
        }
        catch (\Exception $ex) {
            self::assertInstanceOf(get_class($e), $ex);
            self::assertEquals($e->getCode(), $ex->getCode());
            if (strlen($e->getMessage()) > 0) {
                self::assertStringContainsString($e->getMessage(), $ex->getMessage());
            }
            return;
        }
        self::fail(get_class($e) . ' is not thrown.');
    }

    public static function forcedCallize($callable, $method = null)
    {
        if (func_num_args() == 2) {
            $callable = func_get_args();
        }

        if (is_string($callable) && strpos($callable, '::') !== false) {
            $parts = explode('::', $callable);
            $method = new \ReflectionMethod($parts[0], $parts[1]);
            if (!$method->isPublic() && $method->isStatic()) {
                $method->setAccessible(true);
                return function () use ($method) {
                    return $method->invokeArgs(null, func_get_args());
                };
            }
        }

        if (is_array($callable) && count($callable) === 2) {
            try {
                $method = new \ReflectionMethod($callable[0], $callable[1]);
                if (!$method->isPublic()) {
                    $method->setAccessible(true);
                    return function () use ($callable, $method) {
                        return $method->invokeArgs($method->isStatic() ? null : $callable[0], func_get_args());
                    };
                }
            }
            catch (\ReflectionException $ex) {
                // __call を考慮するとどうしようもない
            }
        }

        return $callable;
    }
}
