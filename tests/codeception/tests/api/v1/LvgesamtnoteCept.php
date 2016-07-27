<?php

$I = new ApiTester($scenario);
$I->wantTo("Test API call v1/education/Lvgesamtnote/Lvgesamtnote");
$I->amHttpAuthenticated("admin", "1q2w3");
$I->haveHttpHeader("FHC-API-KEY", "testapikey@fhcomplete.org");

$I->sendGET("v1/education/Lvgesamtnote/Lvgesamtnote", array("student_uid" => "0", "studiensemester_kurzbz" => "0", "lehrveranstaltung_id" => "0"));
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();
$I->seeResponseContainsJson(["error" => 0]);