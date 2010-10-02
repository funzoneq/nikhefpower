function prepairDataCurrVSTemp (data)
{
	var current = new Array();
	var temp = new Array();

	for (var i in data)
	{
		current[current.length] = data[i]['curr']
		temp[temp.length] = data[i]['temp']
	}

	return new Array(current, temp);
}

function prepairDataKWHD (data)
{
	var kwhd = new Array();

	for (var i in data)
	{
		kwhd[kwhd.length] = data[i]['kwhd']
	}

	return new Array(kwhd);
}

function prepairLabels (data)
{
	var label = new Array();
	var count = 0;
	for (var i in data)
	{
		if(count % 2 == 0)
		{
			label[count] = data[i]['time']
		}
		count++
	}
	return label
}

function createGraph (canvasID, title, dataArray, titleArray, labelArray)                    
{
	var g = new Bluff.Line(canvasID, '600x300');
	g.title = title;
	g.tooltips = true;

	g.theme_keynote();

	for (var i in dataArray)
	{
		g.data(titleArray[i], dataArray[i]);
	}

	g.labels = labelArray

	g.draw();

	return g;
}
