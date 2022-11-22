<?php

namespace Smoren\Yii2\Auth\components;

use Yii;
use yii\base\InvalidConfigException;
use yii\web\UrlRuleInterface;

/**
 * Class UrlManager
 * Класс позволяет использовать в правилах алиасы ({@alias_name})
 * @package Smoren\Yii2\Auth\components
 */
class UrlManager extends \yii\web\UrlManager
{
    protected function buildRules($ruleDeclarations)
    {
        $builtRules = $this->getBuiltRulesFromCache($ruleDeclarations);
        if($builtRules !== false) {
            return $builtRules;
        }

        $builtRules = [];
        $verbs = 'GET|HEAD|POST|PUT|PATCH|DELETE|OPTIONS';
        foreach($ruleDeclarations as $key => $rule) {
            if(preg_match_all('/\{(@[A-Za-z0-9]+)\}/', $key, $matches)) {
                foreach($matches[1] as $match) {
                    $alias = Yii::getAlias($match, false);
                    if($alias) {
                        $key = str_replace('{' . $match . '}', $alias, $key);
                    }
                }
            }

            if(is_string($rule)) {
                $rule = ['route' => $rule];
                if(preg_match("/^((?:($verbs),)*($verbs))\\s+(.*)$/", $key, $matches)) {
                    $rule['verb'] = explode(',', $matches[1]);
                    $key = $matches[4];
                }
                $rule['pattern'] = $key;
            }
            if(is_array($rule)) {
                $rule = Yii::createObject(array_merge($this->ruleConfig, $rule));
            }
            if(!$rule instanceof UrlRuleInterface) {
                throw new InvalidConfigException('URL rule class must implement UrlRuleInterface.');
            }
            $builtRules[] = $rule;
        }

        $this->setBuiltRulesCache($ruleDeclarations, $builtRules);

        return $builtRules;
    }
}
