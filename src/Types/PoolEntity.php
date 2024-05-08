<?php
/*
 * Patchworks API client lib
 * Copyright (c) 2024 Patrick Durold
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Nodiskindrivea\PatchworksApi\Types;

use function str_replace;
use function strtolower;
use function ucwords;

enum PoolEntity: int
{
    case DATA = 1;
    case ORDERS = 37;
    case ORDER_LINES = 38;
    case ORDER_TAX_LINES = 39;
    case ORDER_LINE_ITEM_DISCOUNTS = 40;
    case ORDER_LINE_ITEMS_TAX_LINES = 41;
    case DISCOUNT_DETAILS = 42;
    case SHIPPING_ADDRESS = 43;
    case SHIPPING_LINE_DISCOUNTS = 44;
    case SHIPPING_LINES = 45;
    case SHIPPING_LINE_TAX_LINES = 46;
    case CUSTOMER = 47;
    case REFUNDS = 48;
    case REFUND_LINE_ITEMS = 49;
    case REFUNDS_ORDER_ADJUSTMENT = 50;
    case REFUND_LINE_ITEM_DISCOUNTS = 51;
    case REFUND_LINE_ITEM_TAX_LINES = 52;
    case REFUND_TRANSACTIONS = 53;
    case PRODUCTS = 54;
    case VARIANT = 55;
    case IMAGES = 56;
    case FULFILLMENTS_LINE_ITEMS = 57;
    case FULFILLMENTS = 58;
    case INVENTORY_ITEMS = 59;
    case INVENTORY_LEVELS = 60;
    case LOCATIONS = 61;
    case GIFTCARDS = 62;
    case RETURN = 63;
    case PURCHASE_ORDERS = 64;
    case STOCK = 65;
    case REPORTS = 66;
    case CANCELLATIONS = 67;
    case CHANNELS = 68;
    case INVOICES = 69;
    case TRANSFER_ORDERS = 70;
    case BILLING_ADDRESS = 71;
    case ADDRESSES = 72;
    case CONTACTS = 73;
    case CATEGORIES = 74;
    case BRANDS = 75;
    case SITE = 76;
    case CHANNEL = 77;

    /**
     * @return string
     */
    public function title(): string
    {
        return match ($this) {
            self::DATA => 'Unspecified (TX?)',
            default => ucwords(strtolower(str_replace('_', ' ', $this->name)))
        };
    }
}
