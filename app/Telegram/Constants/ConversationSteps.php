<?php

namespace App\Telegram\Constants;

class ConversationSteps
{
    // Measurement conversation
    public const MEASUREMENT_SELECT_TYPE = 'select_type';
    public const MEASUREMENT_INPUT_VALUE = 'input_value';
    public const MEASUREMENT_INPUT_DATE = 'input_date';
    public const MEASUREMENT_CONFIRM = 'confirm';
    
    // Macro conversation
    public const MACRO_SELECT_TYPE = 'select_macro_type';
    public const MACRO_INPUT_VALUE = 'input_macro_value';
    public const MACRO_INPUT_DATE = 'input_macro_date';
    public const MACRO_CONFIRM = 'confirm_macro';
    
    // Weight conversation
    public const WEIGHT_INPUT_VALUE = 'input_weight_value';
    public const WEIGHT_INPUT_DATE = 'input_weight_date';
    public const WEIGHT_CONFIRM = 'confirm_weight';
    
    // Sync conversation
    public const SYNC_SELECT_TYPE = 'select_sync_type';
    public const SYNC_INPUT_DATE = 'input_sync_date';
    public const SYNC_CONFIRM = 'confirm_sync';
    
    // Account linking
    public const LINK_GENERATE_CODE = 'generate_code';
    public const LINK_WAIT_CONFIRMATION = 'wait_confirmation';
}