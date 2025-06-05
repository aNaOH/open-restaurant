<?php
// Enumeraciones globales para el sistema

// Página principal del sistema
// CUSTOM_HOMEPAGE: página personalizada, LOGIN: login, MENU: menú, ORDER: pedido
enum EMAIN_PAGE : int {
    case CUSTOM_HOMEPAGE = 0;
    case LOGIN = 1;
    case MENU = 2;
    case ORDER = 3;
}

// Tipos de producto
// STANDARD: producto normal, COMPOSED: producto compuesto, PROMOTION: producto promocional
enum EPRODUCT_TYPE : int {
    case STANDARD = 0;
    case COMPOSED = 1;
    case PROMOTION = 2;
}

// Roles de usuario
// USER: cliente, EMPLOYEE: empleado, ADMIN: administrador
enum EUSER_ROLE : int {
    case USER = 0;
    case EMPLOYEE = 1;
    case ADMIN = 2;
}