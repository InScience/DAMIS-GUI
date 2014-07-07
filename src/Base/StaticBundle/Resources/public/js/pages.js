var unsaved = false;

$(":input").change(function(){ //triggers change in all input fields including text type
    unsaved = true;
});

//noinspection FunctionWithInconsistentReturnsJS
function unloadPage(){
    if(unsaved){
        return Translator.trans('You have unsaved changes on this page.', {}, 'StaticBundle');
    }
}

window.onbeforeunload = unloadPage;