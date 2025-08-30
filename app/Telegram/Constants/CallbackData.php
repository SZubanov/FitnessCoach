<?php

namespace App\Telegram\Constants;

class CallbackData
{
    // Main menu
    public const START = 'start';
    public const HELP = 'help';
    public const SETTINGS = 'settings';
    
    // Measurements
    public const MEASUREMENTS_START = 'measurements_start';
    public const MEASUREMENT_CHEST = 'measurement_chest';
    public const MEASUREMENT_WAIST = 'measurement_waist';
    public const MEASUREMENT_NECK = 'measurement_neck';
    public const MEASUREMENT_BICEPS = 'measurement_biceps';
    public const MEASUREMENT_PELVIS = 'measurement_pelvis';
    public const MEASUREMENT_THIGH = 'measurement_thigh';
    
    // Sync operations
    public const SYNC_START = 'sync_start';
    public const SYNC_WEIGHT = 'sync_weight';
    public const SYNC_FOOD = 'sync_food';
    public const SYNC_FULL = 'sync_full';
    
    // Account linking
    public const LINK_NEW = 'link_new';
    public const LINK_CHECK = 'link_check';
    public const LINK_REMOVE = 'link_remove';
    
    // FatSecret
    public const FATSECRET_CONNECT = 'fatsecret_connect';
    public const FATSECRET_LOGOUT = 'fatsecret_logout';
    
    // Macros (КБЖУ)
    public const MACROS_START = 'macros_start';
    public const MACROS_CALORIES = 'macros_calories';
    public const MACROS_PROTEINS = 'macros_proteins';
    public const MACROS_FATS = 'macros_fats';
    public const MACROS_CARBS = 'macros_carbs';
    
    // Weight
    public const WEIGHT_START = 'weight_start';
    
    // Common actions
    public const CANCEL = 'cancel';
    public const MAIN_MENU = 'main_menu';
    public const BACK = 'back';
    public const DATE_CHANGE = 'date_change';
}