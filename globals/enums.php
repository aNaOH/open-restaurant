<?php

enum EMAIN_PAGE : int {
    case CUSTOM_HOMEPAGE = 0;
    case LOGIN = 1;
    case MENU = 2;
    case ORDER = 3;
}

enum EPRODUCT_TYPE : int {
    case STANDARD = 0;
    case COMPOSED = 1;
    case PROMOTION = 2;
}

enum EUSER_ROLE : int {
    case USER = 0;
    case EMPLOYEE = 1;
    case ADMIN = 2;
}