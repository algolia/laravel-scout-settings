<?php

namespace Algolia\Settings\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp()
    {
        parent::setUp();

        if (!config('scout.prefix')) {
            config(['scout.prefix' => env('SCOUT_PREFIX', '')]);
        }
    }

    /**
     * @param array $envs
     *
     * @return array Original envs
     */
    protected function replaceEnvs(array $envs)
    {
        $org_envs = [];
        foreach ($envs as $env => $value) {
            $org_value = env($env);

            if ($org_value === null) {
                // Currently not set. `putenv('SOME_KEY')`, so without equals sign,
                // will actually unset the env variable.
                $org_envs[$env] = null;
            } else {
                $org_envs[$env] = $org_value;
            }

            if(null === $value) {
                putenv($env);
            } else {
                putenv("{$env}={$value}");
            }
        }

        return $org_envs;
    }

    /**
     * @param array $envs
     *
     * @return array Original envs
     */
    protected function restoreEnvs(array $envs)
    {
        return $this->replaceEnvs($envs);
    }
}
