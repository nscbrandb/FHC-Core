<?php

$I = new ApiTester($scenario);
$I->wantTo("Test API call v1/content/Contentlog/Contentlog");
$I->amHttpAuthenticated("admin", "1q2w3");
$I->haveHttpHeader("FHC-API-KEY", "testapikey@fhcomplete.org");

$I->sendGET("v1/content/Contentlog/Contentlog", array("contentlog_id" => "0"));
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();
$I->seeResponseContainsJson(["error" => 0]);