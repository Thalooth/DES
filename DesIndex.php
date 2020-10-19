<?php

    $text=$_POST["text"];
    $key=$_POST["key"];
    while (strlen($text)<8){            //Adding Blank spaces
        $text=$text." ";
    }
    /*~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Section 1: Key Generation
    ~~~~~~~~~~~~~~~~~~~~~~~~~
    */

    /*
        This function takes in any string and converts it into a string of bits
    */
    function toBinary($str){
        $str=strtolower($str);
        $str_array = str_split($str);
        $bin_str = "";
        //Extracting each character
        foreach ($str_array as $char) {
            $bin = decbin(ord($char));  //function to convert decimal to binary of the the ASCII of the character
            $temp=(string)$bin;         //Converting to string because The zeroes at the beginning will not be displayed in integer format
            while (strlen($temp)<8){    //Adding zeroes
                $temp="0".$temp;
            }
            $bin_str=$bin_str.$temp;    //Appending result string
            //$char = chr(bindec($bin));
           }
        return $bin_str;
    }
    $key_bin_array=str_split(tobinary($key));   //Converting String Key to Binary Array Key

    //This is the format for rearranging the key at the beginning
    $pc1_format=array( 
        57,49,41,33,25,17,9, 
        1,58,50,42,34,26,18, 
        10,2,59,51,43,35,27, 
        19,11,3,60,52,44,36,           
        63,55,47,39,31,23,15, 
        7,62,54,46,38,30,22, 
        14,6,61,53,45,37,29, 
        21,13,5,28,20,12,4 
    );

    //This is the format for rearranging the key at the end of each round
    $pc2_format=array(
        14,17,11,24,1,5, 
        3,28,15,6,21,10, 
        23,19,12,4,26,8, 
        16,7,27,20,13,2, 
        41,52,31,37,47,55, 
        30,40,51,45,33,48, 
        44,49,39,56,34,53, 
        46,42,50,36,29,32 
    );

    /*
    This function can reorder any array based on the format positions from the values of the second array
    */
    function reorder($array1, $array2){
        $result_array=array();
        foreach ($array2 as $pos){
            array_push($result_array,$array1[$pos-1]);
        }
        return $result_array;
    }

    $pc1=reorder($key_bin_array,$pc1_format);   //reordering into pc1 format

    //Deviding the rearranged key into two equal parts C and D
    $c_array=array_chunk($pc1,28)[0];
    $d_array=array_chunk($pc1,28)[1];

    /*
    This function is used to left circular shift any array n number of bits
    */
    function left_circular_shift($array, $n){
        for ($j=0;$j<$n;$j++){
            $temp=$array[0];
            for($i=0;$i<sizeof($array)-1;$i++){
                $array[$i]=$array[$i+1];
            }
            $array[sizeof($array)-1]=$temp;
            return $array;
        }
    }

    /*~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Section 2: Encryption/Decryption
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    */

    //Format to permute the initial text
    $initial_permutation_table_format = array(
        58,50,42,34,26,18,10,2, 
        60,52,44,36,28,20,12,4, 
        62,54,46,38,30,22,14,6, 
        64,56,48,40,32,24,16,8, 
        57,49,41,33,25,17,9,1, 
        59,51,43,35,27,19,11,3, 
        61,53,45,37,29,21,13,5, 
        63,55,47,39,31,23,15,7 
    ); 

    $text_bin_array=str_split(tobinary($text)); //Converting String text to Binary Array text

    //reordering into initial permutation table format
    $initial_permutation_table=reorder($text_bin_array,$initial_permutation_table_format);

    //Deviding the rearranged key into two equal parts left text and right text
    $left_text_array=array_chunk($initial_permutation_table,32)[0];
    $right_text_array=array_chunk($initial_permutation_table,32)[1];

    //Format to expand the right text table
    $expansion_table_format = array(   
        32,1,2,3,4,5,4,5, 
        6,7,8,9,8,9,10,11, 
        12,13,12,13,14,15,16,17, 
        16,17,18,19,20,21,20,21, 
        22,23,24,25,24,25,26,27, 
        28,29,28,29,30,31,32,1 
    ); 

    /*
    Function to find the xor of two arrays
    */
    function xorArray($array1, $array2){
        $result=array();
        for($i=0;$i<sizeof($array1);$i++){
            if($array1[$i]+$array2[$i]==1)
                array_push($result, 1);
            else
                array_push($result, 0);
        }
        return $result;
    }

    /*
    Function to pass data and process it through the substitution boxes:
    Here, the entire table is devided into 8 equal parts of 6 bits each.
    Then each part is passed through different s-box from S1 to S8.
    The outer bits of each 6 bit word represents the row number and the
    inner bits will point to the columns. The output will be eight parts
    each of 4 bits.
    */
    function sbox_process($array1){

        /*
        Substitution boxes are intended for non liner functional transformations. Here 
        eight of each multi-dimensional sub arrays represents the fomat of substition 
        boxes S1, S2,...S8
        */
        $sbox_format = array( 
        array(array( 14, 4, 13, 1, 2, 15, 11, 8, 3, 10, 6, 12, 5, 9, 0, 7),
        array(0, 15, 7, 4, 14, 2, 13, 1, 10, 6, 12, 11, 9, 5, 3, 8), 
        array(4, 1, 14, 8, 13, 6, 2, 11, 15, 12, 9, 7, 3, 10, 5, 0), 
        array(15, 12, 8, 2, 4, 9, 1, 7, 5, 11, 3, 14, 10, 0, 6, 13 )), 

        array(array(15, 1, 8, 14, 6, 11, 3, 4, 9, 7, 2, 13, 12, 0, 5, 10), 
        array(3, 13, 4, 7, 15, 2, 8, 14, 12, 0, 1, 10, 6, 9, 11, 5), 
        array(0, 14, 7, 11, 10, 4, 13, 1, 5, 8, 12, 6, 9, 3, 2, 15), 
        array(13, 8, 10, 1, 3, 15, 4, 2, 11, 6, 7, 12, 0, 5, 14, 9 )), 

        array(array(10, 0, 9, 14, 6, 3, 15, 5, 1, 13, 12, 7, 11, 4, 2, 8), 
        array(13, 7, 0, 9, 3, 4, 6, 10, 2, 8, 5, 14, 12, 11, 15, 1), 
        array(13, 6, 4, 9, 8, 15, 3, 0, 11, 1, 2, 12, 5, 10, 14, 7), 
        array(1, 10, 13, 0, 6, 9, 8, 7, 4, 15, 14, 3, 11, 5, 2, 12)), 

        array(array(7, 13, 14, 3, 0, 6, 9, 10, 1, 2, 8, 5, 11, 12, 4, 15), 
        array(13, 8, 11, 5, 6, 15, 0, 3, 4, 7, 2, 12, 1, 10, 14, 9), 
        array(10, 6, 9, 0, 12, 11, 7, 13, 15, 1, 3, 14, 5, 2, 8, 4), 
        array(3, 15, 0, 6, 10, 1, 13, 8, 9, 4, 5, 11, 12, 7, 2, 14)), 

        array(array(2, 12, 4, 1, 7, 10, 11, 6, 8, 5, 3, 15, 13, 0, 14, 9), 
        array(14, 11, 2, 12, 4, 7, 13, 1, 5, 0, 15, 10, 3, 9, 8, 6), 
        array(4, 2, 1, 11, 10, 13, 7, 8, 15, 9, 12, 5, 6, 3, 0, 14), 
        array(11, 8, 12, 7, 1, 14, 2, 13, 6, 15, 0, 9, 10, 4, 5, 3)), 

        array(array(12, 1, 10, 15, 9, 2, 6, 8, 0, 13, 3, 4, 14, 7, 5, 11), 
        array(10, 15, 4, 2, 7, 12, 9, 5, 6, 1, 13, 14, 0, 11, 3, 8), 
        array(9, 14, 15, 5, 2, 8, 12, 3, 7, 0, 4, 10, 1, 13, 11, 6), 
        array(4, 3, 2, 12, 9, 5, 15, 10, 11, 14, 1, 7, 6, 0, 8, 13)), 

        array(array(4, 11, 2, 14, 15, 0, 8, 13, 3, 12, 9, 7, 5, 10, 6, 1), 
        array(13, 0, 11, 7, 4, 9, 1, 10, 14, 3, 5, 12, 2, 15, 8, 6), 
        array(1, 4, 11, 13, 12, 3, 7, 14, 10, 15, 6, 8, 0, 5, 9, 2), 
        array(6, 11, 13, 8, 1, 4, 10, 7, 9, 5, 0, 15, 14, 2, 3, 12)), 

        array(array(13, 2, 8, 4, 6, 15, 11, 1, 10, 9, 3, 14, 5, 0, 12, 7),
        array(1, 15, 13, 8, 10, 3, 7, 4, 12, 5, 6, 11, 0, 14, 9, 2),
        array(7, 11, 4, 1, 9, 12, 14, 2, 0, 6, 10, 13, 15, 3, 5, 8),
        array(2, 1, 14, 7, 4, 10, 8, 13, 15, 12, 9, 0, 3, 5, 6, 11 ))
        );

        $bin_str="";                        //Used for decimal to binary conversions

        $result=array();                    //Used to store each result in decimal form

        //indices for each value in s-box
        $row=0;
        $column=0;

        //Deviding the block into 8 parts
        for($i=0;$i<8;$i++){
            //extracting first and last bit of each of the eight parts as string
            $row=strval($array1[$i*6]).strval($array1[$i*6+5]);
            
            //extracting the middle bits of each of the eight parts as string
            $column=strval($array1[$i*6+1]).strval($array1[$i*6+2]).strval($array1[$i*6+3]).strval($array1[$i*6+4]);

            //Converting the bit strings to decimal
            $row=bindec($row);
            $column=bindec($column);
            //Applying the indices to the substitution boxes
            array_push($result, $sbox_format[$i][$row][$column]);
        }

        foreach ($result as $num) {
            $bin = decbin($num);        //function to convert decimal to binary of a number
            $temp=(string)$bin;         //Converting to string because The zeroes at the beginning will not be displayed in integer format
            while (strlen($temp)<4){    //Adding zeroes
                $temp="0".$temp;
            }
            $bin_str=$bin_str.$temp;    //Appending result string

        }

        return(str_split($bin_str));

    }

    //Format for the final permutation table
    $permutation_table_format = array(
        16,7,20,21,29,12,28,17, 
        1,15,23,26,5,18,31,10, 
        2,8,24,14,32,27,3,9, 
        19,13,30,6,22,11,4,25 
    );


/*~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
Section 3: Calling each round
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
*/

    //16 loops repreasenting 16 rounds of encryption
    for($round=1;$round<=16;$round++){

        //Deciding the number of left circular shifts for both parts of the key
        if($round==1 or $round==2 or $round==9 or $round==16){
            $c_array=left_circular_shift($c_array, 1);
            $d_array=left_circular_shift($d_array, 1);
        }
        else{
            $c_array=left_circular_shift($c_array, 2);
            $d_array=left_circular_shift($d_array, 2);
        }

        $merged_key=array_merge($c_array,$d_array); //Merges two arrays
        $pc2=reorder($merged_key,$pc2_format);      //reordering the key into pc2 format

        //reordering the right part of text into expansion table format
        $expansion_table=reorder($right_text_array,$expansion_table_format);

        //The expanded right plain text now consists of 48 bits and is XORed with the 48-bit key
        $first_xor_array=xorArray($expansion_table,$pc2);
        
        //Passing the result into substitution boxes
        $sbox_result_array=sbox_process($first_xor_array);

        //Reordering the the table result from the s-boxes to final permutation format
        $permutation_table=reorder($sbox_result_array, $permutation_table_format);

        $temp=$right_text_array;
        //XOR the left half with the result from the above step
        $right_text_array=xorArray($permutation_table, $left_text_array);

        $left_text_array=$temp;
        //These halves will be the inputs for the next round

    }

    //Swapping of left and right text after the end of 16 rounds
    $temp=$right_text_array;
    $right_text_array= $left_text_array;
    $left_text_array=$temp;

    $end_result=array_merge($left_text_array,$right_text_array);

    //applying the inverse permutation (inverse of the initial permutation)
    $end_result=reorder($end_result,$initial_permutation_table_format);

    /*
    Function to extract each character from its binary array
    */
    function toText($array1){
        $end_result_text="";
        $bin_str="";
        foreach($array1 as $bin){
            $bin_str=$bin_str.strval($bin);
            if(strlen($bin_str)==8){
                $char = chr(bindec($bin_str));
                $bin_str="";
                $end_result_text=$end_result_text.$char;
            }
        }
        return $end_result_text;
    }

    print "Your resultant text: ".toText($end_result);
    print_r($key_bin_array);
    print "<br>";
    print_r($end_result);

?>