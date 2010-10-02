from powerbar.beheer.models import Powerbar, Rack, Customer, HistoryCompressed
from django.contrib import admin

class PowerbarAdmin(admin.ModelAdmin):
	list_display = ('bar', 'rack', 'customer', 'fase', 'feed', 'rail')
	search_fields = ('bar',)
	ordering = ('bar',)

class HistoryCompressedAdmin(admin.ModelAdmin):
	list_display = ('bar', 'kwht', 'kwhd', 'curr', 'temp')
	list_filter = ('time',)
	date_hierarchy = 'time'

admin.site.register(Powerbar, PowerbarAdmin)
admin.site.register(Rack)
admin.site.register(Customer)
admin.site.register(HistoryCompressed, HistoryCompressedAdmin)
