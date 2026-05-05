<?php
  class financial{
    private $weekly_goal;
    private $cash_income;
    private $cash_outflow;
    private $last_date;

    //----------------Getters--------------------------------------- 
    function getWeeklyGoal(){
        return $this->weekly_goal;
    }
    function getCashIncome(){
        return $this->cash_income;
    }
    function getCashOutflow(){
        return $this->cash_outflow;
    }
    function getLastDate(){
        return $this->last_date;
    }
    //------------------Setters-------------------------------------
    function setWeeklyGoal($weekly_goal){
        $this->weekly_goal = $weekly_goal;
    }
    function setCashIncome($cash_income){
        $this->cash_income = $cash_income;
    }
    function setCashOutflow($cashoutflow){
        $this->cash_outflow = $cashoutflow;
    }
    function setLastDate($last_date){
        $this->last_date = $last_date;
    }
}
