<?php

/**
 * Created by PhpStorm.
 * User: king
 * Date: 16/6/7
 * Time: 下午7:10
 */

namespace App\Models\Others\Page;

use Illuminate\Pagination\BootstrapThreePresenter;
use Illuminate\Support\HtmlString;

class PagePresenter extends BootstrapThreePresenter
{

    /**
     * Convert the URL window into Bootstrap HTML.
     *
     * @return \Illuminate\Support\HtmlString
     */
    public function render()
    {
        if ($this->hasPages()) {
            return new HtmlString(sprintf(
                '<ul class="pagination">%s %s %s %s %s</ul>',
                $this->getFirstPage(),
                $this->getPreviousButton(),
                $this->getLinks(),
                $this->getNextButton(),
                $this->getLastPage()
            ));
        }

        return '';
    }

    public function getFirstPage($text = '首页')
    {
        if ($this->paginator->currentPage() <= 1) {
            return $this->getDisabledTextWrapper($text);
        }

        $url = $this->paginator->url(1);

        return $this->getPageLinkWrapper($url, $text, 'firstPage');
    }

    /**
     * Get the previous Page pagination element.
     *
     * @param  string  $text
     * @return string
     */
    public function getPreviousButton($text = '上一页')
    {
        // If the current Page is less than or equal to one, it means we can't go any
        // further back in the pages, so we will render a disabled previous button
        // when that is the case. Otherwise, we will give it an active "status".
        if ($this->paginator->currentPage() <= 1) {
            return $this->getDisabledTextWrapper($text);
        }

        $url = $this->paginator->url(
            $this->paginator->currentPage() - 1
        );

        return $this->getPageLinkWrapper($url, $text, 'prev');
    }

    /**
     * Get the next Page pagination element.
     *
     * @param  string  $text
     * @return string
     */
    public function getNextButton($text = '下一页')
    {
        // If the current Page is greater than or equal to the last Page, it means we
        // can't go any further into the pages, as we're already on this last Page
        // that is available, so we will make it the "next" link style disabled.
        if (! $this->paginator->hasMorePages()) {
            return $this->getDisabledTextWrapper($text);
        }

        $url = $this->paginator->url($this->paginator->currentPage() + 1);

        return $this->getPageLinkWrapper($url, $text, 'next');
    }


    public function getLastPage($text = '尾页')
    {
        if (! $this->paginator->hasMorePages()) {
            return $this->getDisabledTextWrapper($text);
        }

        $url = $this->paginator->url($this->lastPage());

        return $this->getPageLinkWrapper($url, $text, 'lastPage');
    }
}