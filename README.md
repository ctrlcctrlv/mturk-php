mturk-php
=========

Complete Mechanical Turk API written in PHP that uses the same names as the official documentation

mturk.php is a small library that sends requests to Mechanical Turk. It is much simpler than other libraries which redefine every function that Mechanical Turk recognizes. This saves you time so you don't have to worry about the library, just the Mechanical Turk API.

mturk.php is written in the spirit of my original mTurk library, [mturk.py](https://github.com/ctrlcctrlv/mturk-python). Most names are kept the same between the two.

Read the official mTurk API docs [here](http://docs.aws.amazon.com/AWSMechTurk/latest/AWSMturkAPI/Welcome.html).

**Example configuration file (mturkconfig.json)**
```json
{
"use_sandbox" : false,
"verify_mturk_ssl" : true,
"aws_key" : "ACCESSID",
"aws_secret_key" : "PASSWORD"
}
```
**Getting your balance**
```php
$m = new MechanicalTurk();
$r = $m->request('GetAccountBalance');
if (MechanicalTurk::is_valid($r))
    echo 'Your balance is: ' . MechanicalTurk::get_response_element($r, 'Amount');
```

**Creating a HIT**
```php
<?php
$question = <<<QUESTION
<?xml version="1.0" encoding="UTF-8"?>
<QuestionForm xmlns="http://mechanicalturk.amazonaws.com/AWSMechanicalTurkDataSchemas/2005-10-01/QuestionForm.xsd">
  <Question>
    <QuestionIdentifier>answer</QuestionIdentifier>
    <QuestionContent>
      <Text>Hello world :^)</Text>
    </QuestionContent>
    <AnswerSpecification>
      <FreeTextAnswer/>
    </AnswerSpecification>
  </Question>
</QuestionForm>
QUESTION;

$qual = array(
    array('QualificationTypeId' => MechanicalTurk::N_APPROVED,
          'Comparator' => 'GreaterThan',
          'IntegerValue' => 18),
    array('QualificationTypeId' => MechanicalTurk::P_APPROVED,
          'Comparator' => 'GreaterThan',
          'IntegerValue' => 75)
);

$reward = array(array('Amount' => 5, 'CurrencyCode' => 'USD'));

$createhit = array("Title" => "Testing mturk-php API",
                   "Description" => "https://github.com/ctrlcctrlv/mturk-php",
                   "Keywords" => "testing, one, two, three",
                   "Reward" => $reward,
                   "Question" => $question,
                   "QualificationRequirement" => $qual,
                   "AssignmentDurationInSeconds" => 90,
                   "LifetimeInSeconds" => (60*60*24));

$m = new MechanicalTurk();
$r = $m->request('CreateHIT', $createhit);
var_dump($r);
var_dump(MechanicalTurk::is_valid($r));
?>
```
If you find any bugs please open a new issue. 
