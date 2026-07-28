<?php

namespace Wpai\AddonAPI;

if ( ! defined( 'ABSPATH' ) ) exit;

class PMXI_Addon_Post_Field extends PMXI_Addon_Field {

    static $repeater_path = 'value';

    public function parseDelimitedValues($value) {
        $delimiter = $value['delim'] ?? ',';
        $values = explode($delimiter, $value['value'] ?? '');
        $values = array_filter($values);
        $values = array_map('trim', $values);
        return $values;
    }

    public function beforeImport($postId, $value, $data, $logger, $rawData) {
        global $wpdb;

        $post_ids = [];
        $values = $this->parseDelimitedValues($value);
        $post_types = $this->args['search_post_type'] ?? [$data['articleData']['post_type']];

        if (empty($values)) {
            return $post_ids;
        }

        foreach ($values as $ev) {
            $relation = false;

            if (ctype_digit($ev)) {
                if (empty($post_types)) {
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- {$wpdb->posts} is an internal table name from $wpdb.
                    $relation = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->posts} WHERE ID = %d", $ev));
                } else {
                    $placeholders = implode(',', array_fill(0, count($post_types), '%s'));
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- {$wpdb->posts} is an internal table name from $wpdb; $placeholders contains only %s tokens.
                    $relation = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->posts} WHERE ID = %d AND post_type IN ($placeholders)", array_merge([$ev], $post_types)));
                }
            }

            if (empty($relation)) {
                if (empty($post_types)) {
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- {$wpdb->posts} is an internal table name from $wpdb.
                    $relation = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->posts} WHERE post_type != %s AND ( post_title = %s OR post_name = %s )", 'revision', $ev, sanitize_title_for_query($ev)));
                } else {
                    $placeholders = implode(',', array_fill(0, count($post_types), '%s'));
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- {$wpdb->posts} is an internal table name from $wpdb; $placeholders contains only %s tokens.
                    $relation = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->posts} WHERE post_type IN ($placeholders) AND ( post_title = %s OR post_name = %s )", array_merge($post_types, [$ev, sanitize_title_for_query($ev)])));
                }
            }

            if ($relation) {
                $post_ids[] = (string) $relation->ID;
            }
        }

        if (empty($this->multiple)) {
            return array_shift($post_ids);
        }

        return $post_ids;
    }
}
