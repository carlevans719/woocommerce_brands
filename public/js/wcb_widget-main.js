var abc = "def";

 var Atmf = angular.module('atmf',['ngSanitize','ui.bootstrap','angularUtils.directives.dirPagination','angular-loading-bar']);

  Atmf.directive("slider", function() {
    return {
        restrict: 'A',
        scope: {
            start: "=",
            end : "=",
            min : "=",
            max:  "=",
            key : "=",
            onchnage : "&",
            onend : "&",

        },

        link: function(scope, elem, attrs ) {

            jQuery(elem).slider({
                range: true ,
                min: parseInt(scope.min),
                max: parseInt(scope.max),
                values: [ scope.start , scope.end ],

                slide: function(event, ui) {
                    scope.$apply(function() {
                        scope.start = ui.values[0];
                        scope.end = ui.values[1];
                        scope.onchnage( scope.key , ui.values[0] , ui.values[1] );
                    });
                },
                stop: function( event, ui ) {
                    scope.$apply(function(){
                        scope.onend();
                    });
                }
            });
        }
    }
});



    Atmf.config(['cfpLoadingBarProvider', function( cfpLoadingBarProvider ) {
         cfpLoadingBarProvider.includeSpinner = false;
    }]);




  Atmf.controller('AtmfFrontEnd', ['$scope','$filter','$http', function( $scope , $filter , $http ){


   // var search_page =  JSON.parse(angular.element('#dso').text());







    if(  angular.isDefined(search_page) ){

        $scope.list =  search_page.metadata.list;

        $scope.seachPostType = search_page.metadata.search_post_type;

        $scope.postsPerPage = search_page.metadata.post_per_page;

        $scope.contentLimit = search_page.metadata.contentLimit;

        $scope.sortData = search_page.metadata.sort_meta;

    }




    $scope.formData = {};
    $scope.formMeta = {};






    $scope.addTometa = function(key, start , end){

        $scope.formMeta[key] = {
            start : start ,
            end  : end
        }
    }
    if( angular.isObject(search_page.post) ){

        $scope.posts = search_page.post;
        $scope.totalItems = $scope.posts.length;

    }







    function throughItem(item , id ){


        //if( item.parent_taxonomy ){

            angular.forEach( item.alloption , function(option , key){

                if ( $scope.blankarray.indexOf( parseInt(key) ) == -1) {
                    $scope.blankarray.push( parseInt(key) );
                }

            });


        //}


        if( angular.isArray(item.items)){
            throughItem(item.items[0] , parseInt(id) );
        }







    }


    $scope.selected_taxonomy = [];


    $scope.grabResult = function(scope,model, ngitem){



        if( model == true && scope.key != undefined ) {

            if ( $scope.selected_taxonomy.indexOf( parseInt(scope.key) ) == -1) {
                $scope.selected_taxonomy.push( parseInt(scope.key) );
            }

            //  $scope.selected_taxonomy_refs.push(ngitem);
        }
            //else if( angular.isNumber(parseInt(model) ) ){
            //    //console.log(model);
            //
            //    if ( $scope.selected_taxonomy.indexOf( parseInt(model) ) == -1) {
            //        $scope.selected_taxonomy.push( parseInt(model) );
            //    }
            //
            //}

        else {

            $scope.blankarray = [];


            var taxonomy_name = [];

            //if(angular.isNumber(parseInt(model)) ) {
            //    scope.key = parseInt(model);
            //}

            // get all the options from the unselect checkbox and taxonomy name
            if( angular.isArray(ngitem.items) ){

                taxonomy_name.push(ngitem.taxonomy);
                throughItem( ngitem.items[0] , parseInt(scope.key) );

            }


           $scope.selected_taxonomy.splice( $scope.selected_taxonomy.indexOf(parseInt(scope.key)  ), 1 );






           angular.forEach( $scope.blankarray , function(blank_key) {

               // splicing all options from root unselect

               if(jQuery.inArray(blank_key ,$scope.selected_taxonomy) != -1){
                   $scope.selected_taxonomy.splice( $scope.selected_taxonomy.indexOf(parseInt(blank_key)  ), 1 );
               }

               // true / false change in the formdata
               angular.forEach($scope.formData , function(cat,taxonomy){

                    if( jQuery.inArray(taxonomy ,taxonomy_name) != -1 ){

                        angular.forEach(cat,function(category,category_key){

                            if( category_key == blank_key ){

                                cat[category_key] = false;
                            }
                        });
                    }
                });

           });



        } // else

     //   console.log('selected taxonomy : ',$scope.selected_taxonomy);



        $scope.doFilter();

    }







    $scope.grabMeta = function(){
       $scope.doFilter();
    }


   $scope.isObject = function(scope){

        if(angular.isObject(scope))
            return true;
        else
            return false;
   }


    $scope.filterData = {};


    $scope.doFilter = function(){

            $scope.filterData.taxonomy = $scope.selected_taxonomy;
            $scope.filterData.metadata = $scope.formMeta;
            $scope.filterData.alltaxonomies = $scope.formData;
            $scope.filterData.post_type = search_page.metadata.search_post_type;
            $scope.filterData.sort_meta = search_page.metadata.sort_meta;

            $scope.loading = true;
console.log($scope.filterData);
            $http({
                method: 'POST',
                url: atmf.ajaxurl+'?action=atmf_do_filter' ,
                data: jQuery.param({ 'filter': $scope.filterData }),
                headers : { 'Content-Type': 'application/x-www-form-urlencoded' }
            }).then(function(e){
                $scope.posts = [];
                $scope.posts = e.data;
console.log(e);
                $scope.totalItems = $scope.posts.length;
                $scope.loading = false;
            });
    }

    $scope.doReset = function(){
            location.reload();
    }

    $scope.postView = 'list';

  }]);
