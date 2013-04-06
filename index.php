<?php

/*
  Copyright (C) 2013  Arthur Tumanyan <arthurtumanyan@yahoo.com>

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

include_once 'RadiusControl.php';
include_once 'config.inc';

/* Here goes data neccessary to create/generate a user */

$params['username'] = 'a.tumanyan';
$params['password'] = 'KapanCityForever-3010';
$params['groupname'] = 'XustupUsers';
$params["expire"] = "21 September 2999";
$params["simultaneous"] = 2;
$params["count"] = 1986;
$params["prefix"] = "N3HDEH_";
$params["suffix"] = "HAY";

$rfc = new RadiusControl();

//$rfc->setHost("localhost");
//$rfc->setPort(3306);
//$rfc->setUsername("hitman");
//$rfc->setPasswd("bmblahananimqezmatax");
//$rfc->setDBName("radius");
//$rfc->setPasswdType('md5');
//$rfc->setIOFormat('json');

//$rfc->FlushAccounts(); /* Be very carefully with this function */
//printf("Adding account<br />");
//var_dump($rfc->CreateAccount($params));

//printf("Changing group<br />");
//$params['groupname'] = 'Maralner';
//var_dump($rfc->ChangeAccoutGroup($params));

//printf("Changing password<br />");
//$params['password'] = 'TalatilaTalaLala';
//var_dump($rfc->ChangeAccountPasswd($params));

//printf("Suspending account<br />");
//var_dump($rfc->SuspendAccount($params));

//printf("Unsuspending account<br />");
//var_dump($rfc->UnsuspendAccount($params));

//printf("Getting online users<br />");
//var_dump($rfc->GetOnlineAccounts());

//printf("Getting history<br />");
//var_dump($rfc->GetLoginHistory($params));

//printf("Deleting user<br />");
//var_dump($rfc->DeleteAccount($params));

//printf("Generating 10 users<br />");
//var_dump($rfc->generateAccounts($params));