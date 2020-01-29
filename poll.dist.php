<?php

// example config file to override section handles etc
// these settings would override the settings set from the CP
return [
    // configure handles for installation

    // the section containing polls
    // default: pollSection
    'sectionHandle' => '',

    // the field that is generated to select a poll
    // default: selectedPoll
    'selectPollFieldHandle' => '',

    // the matrix that contains 'answers'
    // default: pollAnswerMatrix
    'answerMatrixFieldHandle' => '',

    // within the answerMatrixFieldHandle, a block is created that will contain the answers.
    // this is the handle for that block.
    // default: answer
    'matrixBlockAnswerHandle' => ''

];