# Pull the feed data ...
insertFeedHtml = (feedAggregateObject,insertAfter = "before_feeds") ->
  feedCount = Object.size(feedAggregateObject.feedData)
  i = 0
  total_time = 0;
  if insertAfter.search("#") isnt 0
    insertAfter = "##{insertAfter}"
  if not $(insertAfter).exists()
    $($(".entry-content")[0]).append("<span id='#{insertAfter}'></span>")
  for feedObject in feedAggregateObject.feedData
    if feedObject.raw is true
      # We want to skip raw objects, they have to be handled manually
      continue
    args = "random=#{feedObject.random}&decode_entities=#{feedObject.decode_entities}&limit=#{feedObject.limit}&override_feed_title=#{feedObject.override_feed_title}&url=#{feedObject.url}"
    console.log("Pinging","#{feedAggregateObject.embedFeedAsyncTarget}?#{args}")
    $.get(feedAggregateObject.embedFeedAsyncTarget,args,"json")
    .done (result) ->
      # Insert it into the DOM
      $(insertAfter).after(result.html)
      console.log("Loaded feed data from",result.url,"in #{result.execution_time} ms")
    .fail (result,status) ->
      console.error("Failed to get feed data for",feedObject.url)
      console.warn(result,status)
    .always (result) ->
      i++
      total_time += result.execution_time
      if i is feedCount
        # Stop the loading animation
        stopLoad()
        console.log("Finished in #{total_time} ms")

###
# Helpers
###

Object.size = (obj) ->
  size = 0
  size++ for key of obj when obj.hasOwnProperty(key)
  size

jQuery.fn.exists = -> jQuery(this).length > 0

# Animations

animateLoad = (d=50,elId="#status-container") ->
  try
    if $(elId).exists()
      sm_d = roundNumber(d * .5)
      big = $(elId).find('.ball')
      small = $(elId).find('.ball1')
      big.removeClass('stop nodisp')
      big.css
        width:"#{d}px"
        height:"#{d}px"
      offset = roundNumber(d / 2 + sm_d/2 + 9)
      offset2 = roundNumber((d + 10) / 2 - (sm_d+6)/2)
      small.removeClass('stop nodisp')
      small.css
        width:"#{sm_d}px"
        height:"#{sm_d}px"
        top:"-#{offset}px"
        'margin-left':"#{offset2}px"
      return true
    false
  catch e
    console.log('Could not animate loader', e.message);

stopLoad = (elId="#status-container",fadeOut = 500) ->
    try
      if $(elId).exists()
        big = $(elId).find('.ball')
        small = $(elId).find('.ball1')
        big.addClass('bballgood ballgood')
        small.addClass('bballgood ball1good')
        delay fadeOut, ->
          big.addClass('stop nodisp')
          big.removeClass('bballgood ballgood')
          small.addClass('stop nodisp')
          small.removeClass('bballgood ball1good')
    catch e
      console.log('Could not stop load animation', e.message);


stopLoadError = (elId="#status-container",fadeOut = 1500) ->
  try
    if $(elId).exists()
      big = $(elId).find('.ball')
      small = $(elId).find('.ball1')
      big.addClass('bballerror ballerror')
      small.addClass('bballerror ball1error')
      delay fadeOut, ->
        big.addClass('stop nodisp')
        big.removeClass('bballerror ballerror')
        small.addClass('stop nodisp')
        small.removeClass('bballerror ball1error')
  catch e
    console.log('Could not stop load error animation', e.message);


###
# Onloads
###

$ ->
  $("<link/>",{
    rel:"stylesheet"
    type:"text/css"
    media:"screen"
    href:"#{window.feedBlobObject.pluginPath}js/loadAnimations.css"
    }).appendTo("head")
  try
    # Begin the loading animation
    animateLoad()
    insertFeedHtml(window.feedBlobObject)
  catch e
    console.error("Couldn't insert feed items into page:",e.message)
    console.warn(e.stack)
    # Kill any loading animation
    stopLoadError()
