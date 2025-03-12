<?php

namespace Nodiskindrivea\PatchworksApi\Types;

enum RequestOption: string
{
    public const DEFAULT_OPTIONS = [
        RequestOption::ITEMS_PER_PAGE->value => 250,
        RequestOption::MAX_PAGES->value => 50,
        RequestOption::TRANSFER_TIMEOUT->value => 10,
        RequestOption::CONNECT_TIMEOUT->value => 10,
        RequestOption::INACTIVITY_TIMEOUT->value => 10
    ];

    case CONNECT_TIMEOUT = 'connect_timeout';
    case TRANSFER_TIMEOUT = 'transfer_timeout';
    case INACTIVITY_TIMEOUT = 'inactivity_timeout';
    case ITEMS_PER_PAGE = 'per_page';
    case MAX_PAGES = 'max_pages';
}
