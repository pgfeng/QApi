<?php

namespace QApi\Cache;

use DateInterval;
use QApi\Config\Cache\Remote;
use QApi\Request;
use QApi\Response;
use QApi\Router;

class RemoteAdapter implements CacheInterface
{

    public function __construct(protected Remote $config)
    {
    }

    private function getKey($key): string
    {
        return $this->config->scheme . '://' . $this->config->host . ':' . $this->config->port . '/' . $this->config->path . '/' . urlencode($key);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->getKey($key));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($this->config->password) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->config->username . ':' . $this->config->password);
        }
        $response = curl_exec($ch);
        $http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code !== 200) {
            return null;
        } else {
            return unserialize($response, [
                'allowed_classes' => true,
            ]);
        }
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->getKey($key));
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'value' => serialize($value),
            'ttl' => serialize($ttl),
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($this->config->password) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->config->username . ':' . $this->config->password);
        }
        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code === 200) {
            return true;
        } else {
            return false;
        }
    }

    public function delete(string $key): bool
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->getKey($key));
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($this->config->password) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->config->username . ':' . $this->config->password);
        }
        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code === 200) {
            return true;
        } else {
            return false;
        }
    }

    public function clear(): bool
    {
        throw new \RuntimeException('Not support');
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = $this->get($key, $default);
        }
        return $data;
    }

    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    public function has($key): bool
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->getKey($key));
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD');
        if ($this->config->password) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->config->username . ':' . $this->config->password);
        }
        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code === 200) {
            return true;
        } else {
            return false;
        }
    }

    public static function route($configName, $username = '', $password = ''): void
    {
        Router::all('/{configName}/{key}', function (Request $request, Response $response) use ($configName, $username, $password) {
            if ($request->server->get('PHP_AUTH_USER') !== $username || $request->server->get('PHP_AUTH_PW') !== $password) {
                return $response->withHeader('WWW-Authenticate', 'Basic realm="QApiCacheServer"')->setCode(504);
            }
            $cache = Cache::initialization($configName);
            if ($request->isMethod('GET')) {
                $data = $cache->get(urldecode($request->arguments->get('key')));
                if ($data) {
                    return $response->setRaw()->setData(
                        serialize($data),
                    );
                } else {
                    return $response->setCode(404);
                }
            } else if ($request->isMethod('POST')) {
                if ($cache->set(urldecode($request->arguments->get('key')), unserialize($request->post->get('value'), [
                    'allowed_classes' => true,
                ]), unserialize($request->post->get('ttl')))) {
                    return $response->setCode(200);
                } else {
                    return $response->setCode(500);
                }
            } else if ($request->isMethod('DELETE')) {
                if ($cache->delete(urldecode($request->arguments->get('key')))) {
                    return $response->setCode(200);
                } else {
                    return $response->setCode(500);
                }
            } else if ($request->isMethod('HEAD')) {
                if ($cache->has(urldecode($request->arguments->get('key')))) {
                    return $response->setCode(200);
                } else {
                    return $response->setCode(404);
                }
            }
            return $response->setCode(405);
        });
    }
}