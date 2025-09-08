<?php
//*****************************************************************************
//26.03.2013 Claude Hübscher
//-----------------------------------------------------------------------------
//*****************************************************************************
class log
{
    private $db;

    function __construct($db = null)
    {
        // Immer das Singleton verwenden, niemals new db()
        $this->db = $db ?: db::getInstance();
    }

    public function write_to_log($category, $text)
    {
        $log_user = isset($_SESSION['login_user']) ? $_SESSION['login_user']->fullname : 'Unknown';
        $arr_fields = [
            'log_category' => $category,
            'log_user' => $log_user,
            'log_text' => $text
        ];
        $this->db->insert($arr_fields,'log');
    }
}


?>