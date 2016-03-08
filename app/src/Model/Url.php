<?php

namespace PopSpider\Model;

use Pop\Http\Response;

class Url
{

    /**
     * @var Response
     */
    protected $response = null;
    protected $url      = '';
    protected $elements = [];

    public function __construct($url)
    {
        $this->url = $url;
    }

    public function response()
    {
        return $this->response;
    }

    public function getCode()
    {
        return (null !== $this->response) ? $this->response->getCode() : null;
    }

    public function getMessage()
    {
        return (null !== $this->response) ? $this->response->getMessage() : null;
    }

    public function isSuccess()
    {
        return (null !== $this->response) ? $this->response->isSuccess() : false;
    }

    public function isRedirect()
    {
        return (null !== $this->response) ? $this->response->isRedirect() : false;
    }

    public function isError()
    {
        return (null !== $this->response) ? $this->response->isClientError() : false;
    }

    public function parse($baseUrl, $context, array $tags)
    {
        $dom            = null;
        $contentType    = null;
        $this->response = Response::parse($this->url, $context);

        if (null !== $this->response->getHeader('Content-type')) {
            $contentType = $this->response->getHeader('Content-type');
        } else if (null !== $this->response->getHeader('Content-Type')) {
            $contentType = $this->response->getHeader('Content-Type');
        }

        if ((null !== $contentType) && (stripos($contentType, 'text/html') !== false)) {
            if ($this->response->getCode() == 200) {
                $oldError = ini_get('error_reporting');
                error_reporting(0);

                $dom = new \DOMDocument();
                $dom->recover = true;
                $dom->strictErrorChecking = false;
                $dom->loadHTML($this->response->getBody());

                error_reporting($oldError);
            }
        }

        if (null !== $dom) {
            foreach ($tags as $tag) {
                switch ($tag) {
                    case 'title':
                        $title = $dom->getElementsByTagName('title');

                        $this->elements['title'] = (null !== $title->item(0)) ?
                            $title->item(0)->nodeValue : null;
                        break;

                    case 'meta':
                        $meta = $dom->getElementsByTagName('meta');

                        if (null !== $meta->item(0)) {
                            foreach ($meta as $m) {
                                if ($m->hasAttribute('name') && $m->hasAttribute('content')) {
                                    if (!isset($this->elements['meta'])) {
                                        $this->elements['meta'] = [];
                                    }
                                    $this->elements['meta'][] = [
                                        'name'    => $m->getAttribute('name'),
                                        'content' => $m->getAttribute('content')
                                    ];
                                }
                            }
                        }
                        break;

                    case 'a':
                        $anchors = $dom->getElementsByTagName('a');

                        if (null !== $anchors->item(0)) {
                            foreach ($anchors as $a) {
                                if (!isset($this->elements['a'])) {
                                    $this->elements['a'] = [];
                                }

                                $href = ($a->hasAttribute('href') ? $a->getAttribute('href') : null);

                                if ((null !== $href) && ($this->isValidHref($href))) {
                                    if (substr($href, 0, strlen($baseUrl)) == $baseUrl) {
                                        $href = substr($href, strlen($baseUrl));
                                    }
                                    $url = substr($this->url, strlen($baseUrl));

                                    if (substr($href, 0, 1) == '/') {
                                        $href = $baseUrl . $href;
                                    } else if (substr($href, 0, 2) == './') {
                                        $href = $baseUrl . $url . substr($href, 1);
                                    } else if (strpos($href, '../') !== false) {
                                        $depth  = substr_count($url, '/');
                                        $levels = substr_count($href, '../');
                                        if ($depth > $levels) {
                                            for ($i = 0; $i < $levels; $i++) {
                                                $url = substr($url, 0, strrpos($url, '/'));
                                            }
                                            $href = $baseUrl . $url . '/' . str_replace('../', '', $href);
                                        } else {
                                            $href = $baseUrl . '/' . str_replace('../', '', $href);
                                        }
                                    }
                                }

                                if ($a->nodeValue != '') {
                                    $value = $a->nodeValue;
                                } else {
                                    $imgs  = $a->getElementsByTagName('img');
                                    $value = (null !== $imgs->item(0)) ? '[image]' : null;
                                }

                                $this->elements['a'][] = array(
                                    'href'  => $href,
                                    'value' => $value,
                                    'title' => ($a->hasAttribute('title') ? $a->getAttribute('title') : null),
                                    'name'  => ($a->hasAttribute('name') ? $a->getAttribute('name') : null),
                                    'rel'   => ($a->hasAttribute('rel') ? $a->getAttribute('rel') : null)
                                );
                            }
                        }
                        break;

                    case 'img':
                        $images = $dom->getElementsByTagName('img');

                        if (null !== $images->item(0)) {
                            foreach ($images as $image) {
                                if (!isset($this->elements['img'])) {
                                    $this->elements['img'] = [];
                                }
                                $this->elements['img'][] = [
                                    'src'   => ($image->hasAttribute('src') ? $image->getAttribute('src') : null),
                                    'alt'   => ($image->hasAttribute('alt') ? $image->getAttribute('alt') : null),
                                    'title' => ($image->hasAttribute('title') ? $image->getAttribute('title') : null),
                                ];
                            }
                        }
                        break;

                    default:
                        $element = $dom->getElementsByTagName($tag);

                        if (null !== $element->item(0)) {
                            foreach ($element as $e) {
                                $this->elements[$tag][] = $e->nodeValue;
                            }
                        }
                }
            }
        }

        return $this->elements;
    }

    public function getElements()
    {
        return $this->elements;
    }

    public function isParsed()
    {
        return (null !== $this->response);
    }

    public function __toString()
    {
        return $this->url;
    }

    protected function isValidHref($href)
    {
        return (($href != '') &&
            (substr($href, 0, 1) != '#') &&
            (substr($href, 0, 1) != '?') &&
            (substr(strtolower($href), 0, 7) != 'mailto:') &&
            (substr(strtolower($href), 0, 4) != 'tel:'));
    }

}