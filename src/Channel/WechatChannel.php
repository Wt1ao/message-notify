<?php

declare(strict_types=1);

namespace MessageNotify\Channel;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use MessageNotify\Exceptions\MessageNotificationException;
use MessageNotify\Template\AbstractTemplate;

class WechatChannel extends AbstractChannel
{
    public function send(AbstractTemplate $template): bool
    {
        $client = $this->getClient($template->getPipeline());

        $option = [
            RequestOptions::HEADERS => [],
            RequestOptions::JSON => $template->wechatBody(),
        ];
        $request = $client->post('', $option);
        $result = json_decode($request->getBody()->getContents(), true);

        if ($result['errcode'] !== 0) {
            throw new MessageNotificationException($result['errmsg']);
        }

        return true;
    }

    private function getClient(string $pipeline)
    {
        $config = $this->config($pipeline);

        $uri['base_uri'] = $config['uri'] ?? 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=';
        $uri['base_uri'] .= $config['token'];

        if (class_exists(\Hyperf\Utils\ApplicationContext::class)) {
            return make(Client::class, [$uri]);
        }

        return new Client($config);
    }

    private function config(string $pipeline)
    {
        $config = $this->getConfig();
        return $config['pipeline'][$pipeline] ?? $config['pipeline'][$config['default']];
    }
}
