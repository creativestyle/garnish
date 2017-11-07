<?php

namespace Creativestyle\Garnish\Storage;


/**
 * Interface for storage plugins that do long-running stuff like uploading to CDN.
 * The heavy-lifting can be done after the response has been already sent to user.
 */
interface DeferredStorageInterface
{
    /**
     * Shall store all buffered data.
     *
     * Will be called after response has been sent to user.
     */
    public function processDeferred();
}