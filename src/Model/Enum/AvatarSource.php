<?php

namespace CleverCloud\Sdk\Model\Enum;

/**
 * Where Clever Cloud derives a user or organisation avatar from. The API
 * computes the avatar URL from the chosen source; a URL cannot be supplied.
 */
enum AvatarSource: string
{
    case Gravatar = 'gravatar';
    case Github = 'github';
}
