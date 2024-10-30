jQuery(document).ready(
		function($) {

			
			function hexToRGB(hex, alpha) {
			    var r = parseInt(hex.slice(1, 3), 16),
			        g = parseInt(hex.slice(3, 5), 16),
			        b = parseInt(hex.slice(5, 7), 16);

			    if (alpha) {
			        return "rgba(" + r + ", " + g + ", " + b + ", " + alpha + ")";
			    } else {
			        return "rgb(" + r + ", " + g + ", " + b + ")";
			    }
			}

			
			
			
			console.log("++++ ready to show timeline...");
			// DOM element where the Timeline will be attached
			var container = document.getElementById('visualization');
			if (container == null){
				return;
			}
			console.log("container found : " + container);
			
			// get attrs
			var type = container.getAttribute('type');
			var category = container.getAttribute('category');
			var separator = container.getAttribute('separator');
			var delimiter = container.getAttribute('delimiter');
			var data_path = container.getAttribute('data_path');
			var url = container.getAttribute('url');
			var user_groups = maxicharts_timeline_ajax_object.groups;
			var user_colors = maxicharts_timeline_ajax_object.colors;
			var maxentries = maxicharts_timeline_ajax_object.maxentries;
			console.log("type found : " + type);
			console.log("data_path found : " + data_path);
			console.log("url found : " + url);
			console.log("groups found : " + user_groups);
			console.log("colors found : " + user_colors);
			var data;
			var items = null;
			var timeline = null;
			// Create a Timeline

			var timelineOptions = {};
		
			var heightSetting = maxicharts_timeline_ajax_object.height;
			var widthSetting = maxicharts_timeline_ajax_object.width;
			
			
			if (!heightSetting) {
				heightSetting = '500px';
			}
			if (!widthSetting) {
				widthSetting = '100%';
			}
			
			timelineOptions = {
					"height" : heightSetting,
					"width" : widthSetting
				}
			
			var colors = [];
			if (user_colors != null) {
				// console.log( user_colors);
				// colors_array = user_colors.split(',');
				user_colors.forEach(function(element) {
					// console.log(element);
					colors.push({
						id : element,
						content : element
						
					});
				});

			}
			
			var groups = [];
			if (user_groups != null) {
				groups_array = user_groups.split(',');
				let idx = 0;
				groups_array.forEach(function(element) {
					// console.log(element);
					groups.push({
						id : element,
						content : element,
						color : colors[idx].content,
						style : "color: white; background-color: "+colors[idx].content+";"
					});
					
					idx++;
				});

			}
			
			

			console.log(groups);			
			console.log(colors);
			
			if (url != null) {
				console.log("create timeline from url : " + url);

			} else if (type != null && type != '') {
				console.log("Processing timeline based on post type "+type);
				// process WP posts
				// maxicharts_timeline_ajax_object.ajax_url
				
				if (type == 'gravity_flow'){
					ajax_action = 'maxicharts_get_gf_activity';
				} else if (type == 'mchartstl_event') {
					ajax_action = 'maxicharts_get_posts_list';
				} else if (type == 'post'){
					ajax_action = 'maxicharts_get_posts_list';
				}
				
				// call getPostsList on server side to get the post list
				var data = {
					action : ajax_action,
					form_id : maxicharts_timeline_ajax_object.form_id,
					entry_id : maxicharts_timeline_ajax_object.entry_id,
					post_type : type,
					max_entries : maxentries,
				// posts_offset: posts_offset
				};
				console.log(data);
				$.post(maxicharts_timeline_ajax_object.ajax_url, data,
						function(posts_list) {
							console.log("list of posts retrieved from backend:");
							console.log(posts_list);
							
							if (posts_list.length == 0){
								console.log("No events to display in timeline...")
							} else {
								if (items == null) {

									items = new vis.DataSet(posts_list);
								} else {
									items.add(posts_list)
								}
								console.log(items);
								if (timeline == null) {								

									timeline = new vis.Timeline(container, items,
											groups, timelineOptions);
								} else {
									console.log("timeline already exists");
								}
							}					

						});

			} else if (data_path != null) {
				// Create a DataSet (allows two way data-binding)
				var options = {
					"separator" : separator,
					"delimiter" : delimiter,
				};
				console.log(options);
				var dataSources = data_path.split(',');

				console.log(dataSources);

				dataSources.forEach(function(element) {
					console.log("Current source: " + element);
					$.ajax({
						type : "GET",
						url : element,
						dataType : "text",
						success : function(response) {

							// Used to convert multi-line CSV data into an array
							// objects containing the data as key-value (ie
							// header:value) pairs.
							// console.log(response);
							
							data = $.csv.toObjects(response, options);
							console.log(data);
							
							
							// clean and set colors to items:
							// "color: red; background-color: pink;".
							
							data.forEach(function(element) {
								// console.log(element);
								if (element.end == null || element.end == ''){
									delete element.end;
								}
								if (element.group == null || element.group == ''){
									delete element.group;
								}
								if (element.title == null || element.title == ''){
									delete element.title;
								}
								if (colors != null){
									if (element.group){
										console.log(element);
										var groupObj = groups.filter(obj => {
											  return obj.id === element.group;
											})
										console.log(groupObj);
										if (groupObj.length > 0){
										var groupColor = groupObj[0].color;
										
										rgbColorForeground = hexToRGB(groupColor, 1.0);
										rgbColorBackground = hexToRGB(groupColor, 0.2);
										
										if (element.type == "background"){
											let backgroundColor = 'rgba(0,0,0,.03)';
											element.style = "color: black; background-color: "+backgroundColor+";";	
										} else {
											element.style = "color: white; background-color: "+rgbColorForeground+";";
										}			
										}
									}
									if (element.type == "background"){
									let backgroundColor = 'rgba(0,0,0,.01)';
									element.style = "color: black; background-color: "+backgroundColor+";";	
									}
								}
							});
							// var items = new vis.DataSet(data);
							// console.log(items);
							// Configuration for the Timeline
							if (items == null) {
								items = new vis.DataSet(data);
							} else {
								items.add(data)
							}

							if (timeline == null) {

								// timeline = new vis.Timeline(container, items,
								// timelineOptions);
								timeline = new vis.Timeline(container, items,
										groups, timelineOptions);

							} else {

							}

						}
					});
				});

			}

			function onMouseover(event) {
				var properties = timeline.getEventProperties(event);

			}
			container.addEventListener('mouseover', onMouseover);

			console.log("adding buttons listeners");
			
			groups.forEach(function(element) {
				console.log(element);
				elementOfId = document.getElementById(element.id);
				if (elementOfId){
				elementOfId.onclick = function() {
					console.log("buttons "+element.id+" clicked");
		
					// create an array that holds items in view
					var itemsInRange = items.get({
						filter: function (item) {
							return (
									item.group == element.id
							);
						}
					})
						
					console.log(itemsInRange);
   
    				let result = itemsInRange.map(a => a.id);
    				
    				var startDates = itemsInRange.map(function(item){
    				    return moment(item.start, 'YYYY-MM-DD');
    				});
    				var endDates = itemsInRange.map(function(item){
    				if (item.end != null && item.end != ''){
    						return moment(item.end, 'YYYY-MM-DD');	
    				}
    				    
    				});
    				
    				var endDates = endDates.filter(function (el) {
    					  return el != null && el != 'NaN';
    					});
    				
    				console.log(startDates);
    				let minStart = moment.min(startDates);
    				console.log(endDates);
    				let maxEnd = moment.max(endDates);
    				
    				console.log(minStart);
    				console.log(maxEnd);
    		
    				
					timeline.focus(result);
					timeline.setWindow(minStart, maxEnd, {
						animation : true
					});
					
					

				};
				}
			});
			

			document.getElementById('lastMonth').onclick = function() {
				console.log("buttons 1 clicked");
				const startOfMonth = moment().subtract(3, 'months').startOf(
						'month').format('YYYY-MM-DD');
				const endOfMonth = moment().subtract(1, 'months')
						.endOf('month').format('YYYY-MM-DD');
				timeline.setWindow(startOfMonth, endOfMonth);
			};
			document.getElementById('lastYear').onclick = function() {

				const start = moment().subtract(1, 'year').startOf('year')
						.format('YYYY-MM-DD');
				const end = moment().subtract(1, 'year').endOf('year').format(
						'YYYY-MM-DD');

				timeline.setWindow(start, end, {
					animation : true
				});
			};

			document.getElementById('lastFiveYears').onclick = function() {
				const start = moment().subtract(5, 'year').startOf('year')
						.format('YYYY-MM-DD');
				const end = moment().subtract(1, 'year').endOf('year').format(
						'YYYY-MM-DD');

				timeline.setWindow(start, end, {
					animation : true
				});
			};

			document.getElementById('fit').onclick = function() {
				/*
				 * var list = groups.filter(function(item) { return (item.id !=
				 * '');
				 * 
				 * });
				 * 
				 * timeline.setGroups(list);
				 */
				timeline.fit();
			};

			console.log("adding buttons listeners done!");
		});