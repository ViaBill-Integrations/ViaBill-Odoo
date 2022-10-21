##Viabill Example Library

This is an example api Library for Viabill written in PHP to help developers who want to implement Viabill to their projects.


##RUNNING EXAMPLES WITH PHP

```
composer install
```

All you have to do is 

```
php example/checkout.php
```

##Docker

if you want to run in docker all you have to do is

```
docker-compose up
```

For composer install

```
docker exec app_library_viabill composer install
```

Then you can run examples like below

```
docker exec app_library_viabill php example/checkout.php
```
##Examples

You can find;   

- Checkout
- Capture
- Refund
- Cancel
- MyViabill (Retrieving my-viabill link)

For experimenting you must enter your ApiKey and SecretKey to ViabillExample class. 

In every checkout you must change the constant TRANSACTION_ID and ORDER_ID because these values must be unique.

##Before going live

You should change addon/test endpoints with your addon name in ViabillServices class.