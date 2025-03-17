<?php

class Restaurant {
    public string $name;
    public string $location;
    public Homepage $homepage;
    public EMAIN_PAGE $mainPage = EMAIN_PAGE::LOGIN;
    public string $mainColor;
    public bool $codesEnabled;
    public bool $loyaltyEnabled;
}