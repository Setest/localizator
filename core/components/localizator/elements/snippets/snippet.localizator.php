<?php
$localizator = $modx->getService('localizator');

$class = $modx->getOption('class', $scriptProperties, 'modResource', true);
$localizator_key = $modx->getOption('localizator_key', $scriptProperties, $modx->getOption('localizator_key', null), true);

// Start build "where" expression
$where = array(
    'localizator.key' => $localizator_key,
);

// Join tables
$leftJoin = array(
    'localizator' => array('class' => 'localizatorContent', 'on' => "`localizator`.`resource_id` = `{$class}`.`id`"),
);

$select = array(
    'localizator' => "`{$class}`.*, `localizator`.*, `{$class}`.`id`",
);
$localizatorTVs = array();

if ($includeTVs = $modx->getOption('includeTVs', $scriptProperties, false, true)) {
	$includeTVs = array_map('trim', explode(',', $includeTVs));

	if ($fields = $modx->getOption('localizator_tv_fields', null, false, true)) {
	    $fields = array_map('trim', explode(',', $fields));
	}

	foreach ($includeTVs as $tv){
		if (!empty($tv) && ($fields === false || in_array($tv, $fields))){
			$localizatorTVs[] = $tv;
		}
	}
}

// Add user parameters
foreach (array('where', 'leftJoin', 'select') as $v) {
    if (!empty($scriptProperties[$v])) {
        $tmp = $scriptProperties[$v];
        if (!is_array($tmp)) {
            $tmp = json_decode($tmp, true);
        }
        if (is_array($tmp)) {
            $$v = array_merge($$v, $tmp);
        }
    }
    unset($scriptProperties[$v]);
}

$localizatorProperties = array(
    'where' => $where,
    'leftJoin' => $leftJoin,
    'select' => $select,
    'localizatorTVs' => $localizatorTVs,
    'localizator_key' => $localizator_key,
);

$elementName = $scriptProperties['element'];
$elementSet = array();
if (strpos($elementName, '@') !== false) {
	list($elementName, $elementSet) = explode('@', $elementName);
}
/** @var modSnippet $snippet */
if (!empty($elementName) && $element = $modx->getObject('modSnippet', array('name' => $elementName))) {
	$elementProperties = $element->getProperties();
	$elementPropertySet = !empty($elementSet)
		? $element->getPropertySet($elementSet)
		: array();
	if (!is_array($elementPropertySet)) {$elementPropertySet = array();}
	$params = array_merge(
		$elementProperties,
		$elementPropertySet,
		$scriptProperties,
		$localizatorProperties
	);
	$element->setCacheable(false);
	return $element->process($params);
}
else {
	$modx->log(modX::LOG_LEVEL_ERROR, '[Localizator] Could not find main snippet with name: "'.$elementName.'"');
	return '';
}