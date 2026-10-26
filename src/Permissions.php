<?php

namespace littlemissrobot\ckeditorglossary;

/**
 * Custom permission handles registered by the plugin.
 */
class Permissions
{
    /**
     * Create, edit and delete glossary terms (the “administer glossary terms” capability).
     */
    public const MANAGE_TERMS = 'ckeditor-glossary:manage-terms';

    /**
     * Search for and link to glossary terms from within a CKEditor field.
     */
    public const LINK_TERMS = 'ckeditor-glossary:link-terms';
}
