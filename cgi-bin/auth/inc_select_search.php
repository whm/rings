<?php
/*
 * find_select_items
 *
 * An autoselect list for javascript
 * requires a global javascript array of values/display to search through!
 *
 * param search_source - form field for text box
 * param search_list   - select list to search in
 * param search_array_values  - array of search values
 * param search_array_display - array of search display
 * NOTE:  The search values and search display need to be a 1-1 match!
 *
 * for example, in your page, you HAVE to put in code similar to the following:
 *
 * <script language="javascript" type="text/javascript">
 *  var my_field_search_array  = new Array();
 *  var my_field_search_values = new Array();
 * </script>
 *
 * CRITICAL NOTE:  DO NOT FORGET THE ***NEW*** Array BECAUSE THAT CREATES AN
 * ARRAY OBJECT THAT IS PASSED TO THE FUNCTION BY REFERENCE!!!
 * You must do that for EVERY text box you want to have a search for.
 *
 * Then, call make a text box that has the following:
 *
 *  onkeyup="find_select_items(this, 
 *                             this.form.elements['<form_element_to_search>'], 
 *                             my_field_search_values, 
 *                             my_field_search_array);"
 *
 * A full example looks like this:
 *
 * <script language="javascript" type="text/javascript">
 *   var in_list_values  = new Array();
 *   var in_list_display = new Array();
 * </script>'
 *
 * <input type="text" 
 *        name="in_list_search" 
 *        onkeyup="find_select_items(this, this.form.elements['in_list'], in_list_values, in_list_display);">
 * <select name="in_list">
 *  <option value="one">One</option>
 *  <option value="two">Two</option>
 * </select>
 * 
 */
?>

<script language="javascript" type="text/javascript">

var find_select_arr;
function find_select_items(search_source, 
                           search_list, 
                           search_array_values, 
                           search_array_display) {
    
    if (!search_source || 
        !search_list || 
        !search_array_values || 
        !search_array_display) { return false; }

    // *** let's create an array of values on the fly if necessary.
    if (search_array_values.length == 0) {
        for(i = 0; i < search_list.options.length; i++) {
            search_array_values[i]  = search_list.options[i].value;
            search_array_display[i] = search_list.options[i].text;
        }
    }
    
    var search_pattern = search_source.value;
    re = new RegExp(search_pattern, "gi");
    
    // *** Clear the options list
    search_list.length = 0;
    
    var cur_list_item = 0;
    
    // *** Loop through the array and re-add matching options
    for(i = 0; i < search_array_display.length; i++) {
        if(search_array_display[i].search(re) != -1) {
            search_list[cur_list_item++] = 
                new Option(search_array_display[i], search_array_values[i]);
        }
    }
    
    // When options list whittled to one, select that entry
    if(search_list.length == 1) {
        search_list.options[0].selected = true;
        
        // *** now, simulate the user clicking on the item
        var t_type = typeof search_list.click;
        
        // *** for IE, t_type = object, for firefox, it's undefined.  
        // That's our key for now
        if (t_type == 'object') {
            search_list.click();
        } else {
            /* FireFox doesn't work alert (t_type); */
            /* search_list.onclick;                 */
        }
    }
    
    return true;
}

</script>
