function getCheckedValue(radioObj) {
        if (!radioObj)
            return "";
        var radioLength = radioObj.length;
        if (radioLength == undefined)
            if (radioObj.checked)
                return radioObj.value;
            else
                return "";
        for (var i = 0; i < radioLength; i++) {
            if (radioObj[i].checked) {
                return radioObj[i].value;
            }
        }
        return "";
    };