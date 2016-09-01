<?php

$I = new ApiTester($scenario);
$I->wantTo("Test API call v1/organisation/studiengang Studiengang and AllForBewerbung");
$I->amHttpAuthenticated("admin", "1q2w3");
$I->haveHttpHeader("FHC-API-KEY", "testapikey@fhcomplete.org");

$I->sendGET("v1/organisation/studiengang/Studiengang", array("studiengang_kz" => 1));
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();
$I->seeResponseContainsJson(["error" => 0]);
$I->wait();

$I->sendGET("v1/organisation/studiengang/AllForBewerbung");
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();
$I->seeResponseContainsJson(["error" => 0]);
$I->wait();