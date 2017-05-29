<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


/**
 * 각종 path 필터
 *
 * @param       string  $prefix
 * @param       string  $argument
 *
 * @return      string
 */
function filter_get_path($prefix, $argument)
{
    $assets_path = config_item('assets_path');
    $manifest_path = $assets_path.'/rev-manifest.json';

    $get_versioned_asset = function($filename) use ($manifest_path)
    {
        if (!file_exists($manifest_path))
            return $filename;
        $versioned_files = json_decode(file_get_contents($manifest_path), true);
        return isset($versioned_files[$filename]) ? $versioned_files[$filename] : $filename;
    };

    switch ($prefix) {
        case 'asset':
            return base_url($assets_path . '/' . $argument);
            break;
        case 'image':
            return base_url($assets_path . '/images/' . $argument) . '?' . ASSETS_VERSION;
            break;
        case 'css':
            return base_url($assets_path . '/css/' . $get_versioned_asset($argument)) . '?' . ASSETS_VERSION;
            break;
        case 'script':
            return base_url($assets_path . '/js/' . $get_versioned_asset($argument)) . '?' . ASSETS_VERSION;
            break;
        case 'font':
            return base_url($assets_path . '/fonts/' . $argument) . '?' . ASSETS_VERSION;
            break;
        default:
            return '';
    }
}

/**
 * Merges an array with another one. [recursively]
 *
 * @param array|Traversable $arr1 An array
 * @param array|Traversable $arr2 An array
 *
 * @return array The merged array
 */
function filter_array_merge_recursive($arr1, $arr2)
{
    if ($arr1 instanceof Traversable) {
        $arr1 = iterator_to_array($arr1);
    } elseif (!is_array($arr1)) {
        throw new Twig_Error_Runtime(sprintf('The merge filter only works with arrays or "Traversable", got "%s" as first argument.', gettype($arr1)));
    }

    if ($arr2 instanceof Traversable) {
        $arr2 = iterator_to_array($arr2);
    } elseif (!is_array($arr2)) {
        throw new Twig_Error_Runtime(sprintf('The merge filter only works with arrays or "Traversable", got "%s" as second argument.', gettype($arr2)));
    }

    return array_merge_recursive($arr1, $arr2);
}


/**
 * Convert BR tags to nl
 *
 * @param $string string The string to convert
 * @return string The converted string
 */
function br2nl($string)
{
    return preg_replace('/\<br(\s*)?\/?\>/i', "\n", $string);
}
