from django.db import models

FEED_CHOICES = ( ('a', 'Feed A'), ('b', 'Feed B'), ('c', 'Feed C'), ('d', 'Feed D') )

class Powerbar(models.Model):
	bar = models.SmallIntegerField(unique=True)
	rack = models.ForeignKey('Rack')
	customer = models.ForeignKey('Customer')
	feed = models.CharField(max_length=1, choices=FEED_CHOICES)
	rail = models.SmallIntegerField()
	fase = models.SmallIntegerField()

	def __str__(self):
		return u'%s' % (self.bar)

	class Meta:
		ordering = ['bar']
	
class Rack(models.Model):
	rack = models.SmallIntegerField()
	room = models.SmallIntegerField()
	X = models.SmallIntegerField()
	Y = models.SmallIntegerField()
	
	def __str__(self):
                return u'rack: %s room: %s' % (self.rack, self.room)

class Customer(models.Model):
	customer = models.SmallIntegerField()
	name = models.CharField(max_length=255)
	avgKW = models.SmallIntegerField(default=2)
	
	def __str__(self):
                return u'cid: %s name: %s' % (self.customer, self.name)

class History(models.Model):
        bar = models.ForeignKey('Powerbar', db_index=True)
        kwht = models.IntegerField()
        kwhd = models.IntegerField()
        curr = models.IntegerField()
        temp = models.DecimalField(max_digits=5, decimal_places=2)
	time = models.DateTimeField(auto_now=True)
	run = models.ForeignKey('PollerRun', db_index=True)

class HistoryCompressed(models.Model):
        bar = models.ForeignKey('Powerbar')
        kwht = models.IntegerField()
        kwhd = models.IntegerField()
        curr = models.IntegerField()
        temp = models.DecimalField(max_digits=5, decimal_places=2)
	time = models.DateTimeField(auto_now=False)


class PollerRun(models.Model):
	time = models.DateTimeField(auto_now=True)
