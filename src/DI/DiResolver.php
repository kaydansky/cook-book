<?php

namespace Cookbook\DI;

use Exception;
use ReflectionClass;

class DiResolver
{

    /**
     * Build an instance of the given class
     *
     * @param string $class
     * @return mixed
     *
     * @throws Exception
     * @throws \ReflectionException
     */
    public function resolve($class)
    {
        $reflector = new ReflectionClass($class);

        if (! $reflector->isInstantiable()) {
            throw new Exception("[$class] is not instantiable");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $class;
        }

        $parameters = $constructor->getParameters();
        $dependencies = $this->getDependencies($parameters);

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Build up a list of dependencies for a given methods parameters
     *
     * @param array $parameters
     * @return array
     */
    public function getDependencies($parameters)
    {
        $dependencies = array();

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if (is_null($type) || !$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
                $dependencies[] = $this->resolveNonClass($parameter);
            } else {
                $dependencies[] = $this->resolve($type->getName());
            }
        }

        return $dependencies;
    }

    /**
     * Determine what to do with a non-class value
     *
     * @param ReflectionParameter $parameter
     * @return mixed
     *
     * @throws Exception
     */
    public function resolveNonClass($parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new Exception('Missing default value of an argument');
    }

}
