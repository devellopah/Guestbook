<?php

namespace Models;

class Pagination
{

  public int $count_pages = 1;
  public int $current_page = 1;
  public string $uri = '';
  public int $mid_size = 3;
  public int $all_pages = 7;

  public function __construct(
    public int $page = 1,
    public int $per_page = 1,
    public int $total = 1
  ) {
    $this->count_pages = $this->getCountPages();
    $this->current_page = $this->getCurrentPage();
    $this->uri = $this->getParams();
    $this->mid_size = $this->getMidSize();
  }

  private function getCountPages(): int
  {
    return ceil($this->total / $this->per_page) ?: 1;
  }

  private function getCurrentPage(): int
  {
    if ($this->page < 1) {
      $this->page = 1;
    }
    if ($this->page > $this->count_pages) {
      $this->page = $this->count_pages;
    }
    return $this->page;
  }

  public function getStart(): int
  {
    return ($this->current_page - 1) * $this->per_page;
  }

  private function getParams(): string
  {
    $url = $_SERVER['REQUEST_URI'];
    $url = explode('?', $url);
    $uri = $url[0];
    if (isset($url[1]) && $url[1] != '') {
      $uri .= '?';
      $params = explode('&', $url[1]);
      foreach ($params as $param) {
        if (!str_contains($param, 'page=')) {
          $uri .= "{$param}&";
        }
      }
    }
    return $uri;
  }

  public function getHtml(): string
  {
    $back = '';
    $forward = '';
    $start_page = '';
    $end_page = '';
    $pages_left = '';
    $pages_right = '';

    // Tailwind CSS classes for pagination
    $baseClasses = "relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition-colors";
    $activeClasses = "relative inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 border border-blue-600";
    $edgeClasses = "relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition-colors rounded-l-md";
    $edgeRightClasses = "relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition-colors rounded-r-md";

    if ($this->current_page > 1) {
      $back = "<li><a href='" . $this->getLink($this->current_page - 1) . "' class='{$baseClasses}'><i class='fas fa-chevron-left'></i></a></li>";
    }

    if ($this->current_page < $this->count_pages) {
      $forward = "<li><a class='{$baseClasses}' href='" . $this->getLink($this->current_page + 1) . "'><i class='fas fa-chevron-right'></i></a></li>";
    }

    if ($this->current_page > $this->mid_size + 1) {
      $start_page = "<li><a class='{$edgeClasses}' href='" . $this->getLink(1) . "'><i class='fas fa-angle-double-left'></i></a></li>";
    }

    if ($this->current_page < ($this->count_pages - $this->mid_size)) {
      $end_page = "<li><a class='{$edgeRightClasses}' href='" . $this->getLink($this->count_pages) . "'><i class='fas fa-angle-double-right'></i></a></li>";
    }

    for ($i = $this->mid_size; $i > 0; $i--) {
      if ($this->current_page - $i > 0) {
        $pages_left .= "<li><a class='{$baseClasses}' href='" . $this->getLink($this->current_page - $i) . "'>" . ($this->current_page - $i) . "</a></li>";
      }
    }

    for ($i = 1; $i <= $this->mid_size; $i++) {
      if ($this->current_page + $i <= $this->count_pages) {
        $pages_right .= "<li><a class='{$baseClasses}' href='" . $this->getLink($this->current_page + $i) . "'>" . ($this->current_page + $i) . "</a></li>";
      }
    }

    return '<nav aria-label="Messages pagination"><ul class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">' . $start_page . $back . $pages_left . '<li><span class="' . $activeClasses . '">' . $this->current_page . '</span></li>' . $pages_right . $forward . $end_page . '</ul></nav>';
  }

  private function getLink($page): string
  {
    if ($page == 1) {
      return rtrim($this->uri, '?&');
    }

    if (str_contains($this->uri, '&') || str_contains($this->uri, '?')) {
      return "{$this->uri}page={$page}";
    } else {
      return "{$this->uri}?page={$page}";
    }
  }

  private function getMidSize(): int
  {
    return $this->count_pages <= $this->all_pages ? $this->count_pages : $this->mid_size;
  }

  public function __toString(): string
  {
    return $this->getHtml();
  }
}
