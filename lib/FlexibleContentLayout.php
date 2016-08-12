<?php

namespace Understory\ACF;

interface FlexibleContentLayout
{
    /**
     * Value that gets stored by ACF in the database for a flexible content
     * field that tells it which field and in what order. Gets stored as an
     * serialized array of strings. This is that string.
     * @return string
     */
    public function getLayoutName();
}