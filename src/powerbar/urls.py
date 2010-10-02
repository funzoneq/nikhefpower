from django.conf.urls.defaults import *

# Uncomment the next two lines to enable the admin:
from django.contrib import admin
admin.autodiscover()

urlpatterns = patterns('',
    (r'^static/(?P<path>.*)$', 'powerbar.beheer.views.static'),
    (r'^perrack/(?P<rack>\d{1,3})/$', 'powerbar.beheer.views.perrack'),
    (r'^powerbar/(?P<unit>\d{3,4})/$', 'powerbar.beheer.views.index'),
    (r'^rackview/', 'powerbar.beheer.views.rackview'),
    (r'^admin/', include(admin.site.urls)),
)
