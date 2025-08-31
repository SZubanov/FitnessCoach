<?php

namespace App\Telegram\Constants;

class ConversationSteps
{
    // Universal date input step (all conversations start here)
    public const INPUT_DATE = 'input_date';
    
    // Value input steps
    public const INPUT_MEASUREMENT_VALUE = 'input_measurement_value';
    public const INPUT_MACRO_VALUE = 'input_macro_value';
    public const INPUT_WEIGHT_VALUE = 'input_weight_value';
    
    // Action execution (for sync)
    public const EXECUTE_SYNC = 'execute_sync';
    
    // Success and cleanup
    public const SHOW_SUCCESS = 'show_success';
    
    // Account linking
    public const LINK_GENERATE_CODE = 'generate_code';
    public const LINK_WAIT_CONFIRMATION = 'wait_confirmation';
}