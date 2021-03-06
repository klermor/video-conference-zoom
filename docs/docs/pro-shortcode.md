### 1. List meeting registrants

This shortcode can be used to list all author meeting list in frontend and to view registrants for those meeting.

Use: `[vczapi_pro_author_registrants]`

### 2. Show calendar view

You can find more information for this in [PRO version widget documentation.](/vczapi-pro/#calendar-widget).

Use: `[vczapi_zoom_calendar author="" show="" calendar_default_view="dayGridMonth" show_calendar_views="yes"]`

Where,

* **class** = Ability to add custom css classes to modify design of calendar
* **author** = The user id of an Author, will show only the meetings/webinars of that particular author
* **show** = use "meeting" or "webinar" to either show only meetings or only webinars leave empty or do not use if you want to show all.
* **calendar_default_view** = options => dayGridMonth,timeGridWeek,timeGridDay,listWeek
* **show_calendar_views** = give ability to user to see calendar in different views - default value is "no", use "yes" to show other views

### 3. List Meetings with Register Now button

Use: `[vczapi_list_meetings per_page="5" category="test,test2,test3" order="ASC" type="upcoming" show="meeting"]`

**NOTE: This shortcode will show register now button if a meeting is enabled registration.**

Where,

* **per_page** = Number of posts per page
* **category** = Show assigned category lists
* **order** = Show order of posts "DESC" or "ASC"
* **type** = options => upcoming or past
* **show** = options => meeting or webinar