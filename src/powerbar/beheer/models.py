from django.db import models

FEED_CHOICES = ( ('a', 'Feed A'), ('b', 'Feed B'), ('c', 'Feed C'), ('d', 'Feed D') )

class Powerbar(models.Model):
	bar = models.SmallIntegerField(unique=True)
	rack = models.ForeignKey('Rack')
	customer = models.ForeignKey('Customer')
	fase = models.SmallIntegerField()
	feed = models.CharField(max_length=1, choices=FEED_CHOICES)
	distributionunit = models.SmallIntegerField()
	rail = models.SmallIntegerField()

class Rack(models.Model):
	rack = models.SmallIntegerField()
	room = models.SmallIntegerField()
	X = models.SmallIntegerField()
	Y = models.SmallIntegerField()

class Customer(models.Model):
	customer = models.SmallIntegerField()
	name = models.CharField(max_length=255)
	avgKW = models.SmallIntegerField(default=2)

class History(models.Model):
        bar = models.ForeignKey('Powerbar')
        kwht = models.IntegerField()
        kwhd = models.IntegerField()
        curr = models.IntegerField()
        temp = models.DecimalField(max_digits=5, decimal_places=2)
	time = models.DateTimeField(auto_now=True)
