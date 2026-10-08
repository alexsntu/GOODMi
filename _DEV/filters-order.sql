-- Порядок фильтров GOODMi, подготовлено 2026-10-08. Таблица – с префиксом cscart_ (проверить в своей базе).
-- 1) Перед запуском сохранить текущие позиции:
--    SELECT filter_id, position FROM cscart_product_filters WHERE filter_id IN (422,423,437,427,762,761,886,688,687,884,763,1862,532,564,751,495,540,485,569,1639,470,1640,475,534,699,2048,1835,446,491,578,674,585,629,2015,657,577,626,483,551,835,703,1843,567,550,617,582,580,581,486,476,570,536,600,546,609,583,474,2082,554,615,698,685,602,467,1951,566,545,549,637,548,544,543,616,654,684,1637,579,537,624,497,686,679,627,632,451,1852,727,525,571,683,773,758,498,1792,652,833,2095,664,531,740,480,559,823,539,524,447,489,487,631,1641,712,1798,591,552,666,450,593,671,542,678,713,653,563,651,608,645,2050,444,681,665,646,621,739,755,677,756,714,533,824,745,754,1900,844,1848,847,1076,454,449,746,468,558,568,851,692,589,662,625,721,647,614,612,667,556,650,764,572,786,660,735,736,2097,710,944,849,700,639,808,634,1931,866,610,798,1935,869,1883,720,500,469,574,656,729,641,744,672,770,598,636,1877,722,709,529,768,1485,725,837,1462,1499,675,599,642,945,663,743,682,760,799,737,796,441,836,648,1858,1930,1865,848,899,1925,2093,2092,2078,734,882,1937,895,1480,1044,860,482,481,1869,592,472,2086,676,477,759,502,501,2096,1913,605,606,603,879,897,1868,1331,611,1921,527,448,1856,1021,484,1796,1831,1850,2081,797,445,1332,752,1965,2025,493,473,588,530,523,494,499,526,538,943,547,443,1020,1642,1643,738,2049,1974,1071,1040,1832,1836,1851,2018,1132,1966,790,555,701,607,576,1846,668,693,618,705,492,575,587,623,1904,595,649,1975,1072,1799,1833,1837,2020,2083,573,782,1133,863,522,1911,715,630,594,1955,689,1845,695,696,946,702,733,947,541,757,655,690,452,586,694,1976,1073,1800,1838,1853,2021,2084,2094,2026,1338,1476,1927,730,1952,1957,1891,1847,776,1495,777,771,827,948,780,783,774,789,717,949,765,644,706,728,1977,1074,1839,1854,2022,2085,1922,1134,785,2024,633,719,801,842,825,791,820,1496,826,838,769,822,778,803,766,810,864,1901,1978,1075,1840,2023,779,1878,1135,1443,1486,691,732,781,1942,816,1953,852,834,865,1932,794,741,821,1902,1045,1841,2079,1873,1880,1136,1488,718,1501,1924,814,1944,1910,1893,862,807,1933,804,1492,1903,590,875,1874,1879,1137,1920,787,843,853,723,1954,873,881,839,1490,658,1834,2080,861,1908,1864,802,1138,793,788,877,870,880,1936,1491,1909,819,1139,1479,792,1912,831,876,1939,1481,1894,887,1929,726,1041,888,1866,832,1478,1493,1949,716,1482,1896,1498,1934,1882,1887,1875,1881,1914,1928,1946,638,1483,1895,1497,812,892,1876,1795,1494,1943,1484,815,811,894,1871,806,1916,1923,1945,829,1046,871,872,1926,1940,1047,874,896,1915,850,1948,841,1855,898,2043,883,1918,775,1892,867,1022,1857,1917,813,1042,918,900,885,1043,890,1079,891,1049,1048,1884,1867,1475,1889,1886,1870,1050,1890,429,455,518,565,458,430,431,457,516,622,512,513,519,517,459,460,461,462,463,520,514,456,466,465,440,464,507,438,508,1989,511,509,1463,515,670,428,510,439,1644,503,506,488,669,504,620,619,505,635,750,809,2042,749,840,1898,1907,893,1941,1872,1885,911,914,856,915,909,913,889,859,855,854,2098,912,907,906,941,857,905,910,940,903,1906,908,902,1863,916,917,904,901);
-- 2) Новые позиции:
UPDATE cscart_product_filters SET position = 10 WHERE filter_id = 422 AND company_id = 2; -- Цена
UPDATE cscart_product_filters SET position = 20 WHERE filter_id = 423 AND company_id = 2; -- Наличие товара
UPDATE cscart_product_filters SET position = 30 WHERE filter_id = 437 AND company_id = 2; -- Производитель
UPDATE cscart_product_filters SET position = 40 WHERE filter_id = 427 AND company_id = 2; -- Линейка смартфонов
UPDATE cscart_product_filters SET position = 50 WHERE filter_id = 762 AND company_id = 2; -- Объем встроенной памяти
UPDATE cscart_product_filters SET position = 60 WHERE filter_id = 761 AND company_id = 2; -- Объем встроенной памяти
UPDATE cscart_product_filters SET position = 70 WHERE filter_id = 886 AND company_id = 2; -- Объем встроенной памяти
UPDATE cscart_product_filters SET position = 80 WHERE filter_id = 688 AND company_id = 2; -- Объем оперативной памяти
UPDATE cscart_product_filters SET position = 90 WHERE filter_id = 687 AND company_id = 2; -- Объем оперативной памяти
UPDATE cscart_product_filters SET position = 100 WHERE filter_id = 884 AND company_id = 2; -- Объем оперативной памяти
UPDATE cscart_product_filters SET position = 110 WHERE filter_id = 763 AND company_id = 2; -- Объём оперативной памяти
UPDATE cscart_product_filters SET position = 120 WHERE filter_id = 1862 AND company_id = 2; -- Диагональ экрана (дюйм)
UPDATE cscart_product_filters SET position = 130 WHERE filter_id = 532 AND company_id = 2; -- Выходное разрешение
UPDATE cscart_product_filters SET position = 140 WHERE filter_id = 564 AND company_id = 2; -- Модели поддерживаемых устройств
UPDATE cscart_product_filters SET position = 150 WHERE filter_id = 751 AND company_id = 2; -- Место крепления
UPDATE cscart_product_filters SET position = 160 WHERE filter_id = 495 AND company_id = 2; -- Тип клавиатуры
UPDATE cscart_product_filters SET position = 170 WHERE filter_id = 540 AND company_id = 2; -- Интерфейсы
UPDATE cscart_product_filters SET position = 180 WHERE filter_id = 485 AND company_id = 2; -- Световой поток
UPDATE cscart_product_filters SET position = 190 WHERE filter_id = 569 AND company_id = 2; -- Мощность
UPDATE cscart_product_filters SET position = 200 WHERE filter_id = 1639 AND company_id = 2; -- Аромат
UPDATE cscart_product_filters SET position = 210 WHERE filter_id = 470 AND company_id = 2; -- Мощность
UPDATE cscart_product_filters SET position = 220 WHERE filter_id = 1640 AND company_id = 2; -- Наличие подсветки
UPDATE cscart_product_filters SET position = 230 WHERE filter_id = 475 AND company_id = 2; -- Объем
UPDATE cscart_product_filters SET position = 240 WHERE filter_id = 534 AND company_id = 2; -- Диагональ
UPDATE cscart_product_filters SET position = 250 WHERE filter_id = 699 AND company_id = 2; -- Совместимые модели
UPDATE cscart_product_filters SET position = 260 WHERE filter_id = 2048 AND company_id = 2; -- Камера видеонаблюдения
UPDATE cscart_product_filters SET position = 270 WHERE filter_id = 1835 AND company_id = 2; -- Рекомендуемая площадь помещения
UPDATE cscart_product_filters SET position = 280 WHERE filter_id = 446 AND company_id = 2; -- Диагональ экрана
UPDATE cscart_product_filters SET position = 290 WHERE filter_id = 491 AND company_id = 2; -- Способ регулировки
UPDATE cscart_product_filters SET position = 300 WHERE filter_id = 578 AND company_id = 2; -- Общее количество кнопок
UPDATE cscart_product_filters SET position = 310 WHERE filter_id = 674 AND company_id = 2; -- Модели поддерживаемых устройств
UPDATE cscart_product_filters SET position = 320 WHERE filter_id = 585 AND company_id = 2; -- Объем
UPDATE cscart_product_filters SET position = 330 WHERE filter_id = 629 AND company_id = 2; -- Число мегапикселей матрицы
UPDATE cscart_product_filters SET position = 340 WHERE filter_id = 2015 AND company_id = 2; -- Совместимые модели
UPDATE cscart_product_filters SET position = 350 WHERE filter_id = 657 AND company_id = 2; -- Конструкция
UPDATE cscart_product_filters SET position = 360 WHERE filter_id = 577 AND company_id = 2; -- Общая выходная мощность
UPDATE cscart_product_filters SET position = 370 WHERE filter_id = 626 AND company_id = 2; -- Форм-фактор
UPDATE cscart_product_filters SET position = 380 WHERE filter_id = 483 AND company_id = 2; -- Разъем мобильного устройства
UPDATE cscart_product_filters SET position = 390 WHERE filter_id = 551 AND company_id = 2; -- Максимальная нагрузка
UPDATE cscart_product_filters SET position = 400 WHERE filter_id = 835 AND company_id = 2; -- Давление
UPDATE cscart_product_filters SET position = 410 WHERE filter_id = 703 AND company_id = 2; -- Тип питания
UPDATE cscart_product_filters SET position = 420 WHERE filter_id = 1843 AND company_id = 2; -- Рекомендуемая площадь помещения
UPDATE cscart_product_filters SET position = 430 WHERE filter_id = 567 AND company_id = 2; -- Мощность
UPDATE cscart_product_filters SET position = 440 WHERE filter_id = 550 AND company_id = 2; -- Максимальная нагрузка
UPDATE cscart_product_filters SET position = 450 WHERE filter_id = 617 AND company_id = 2; -- Тип увлажнителя
UPDATE cscart_product_filters SET position = 460 WHERE filter_id = 582 AND company_id = 2; -- Объем
UPDATE cscart_product_filters SET position = 470 WHERE filter_id = 580 AND company_id = 2; -- Объем
UPDATE cscart_product_filters SET position = 480 WHERE filter_id = 581 AND company_id = 2; -- Объем
UPDATE cscart_product_filters SET position = 490 WHERE filter_id = 486 AND company_id = 2; -- Световой поток
UPDATE cscart_product_filters SET position = 500 WHERE filter_id = 476 AND company_id = 2; -- Объем
UPDATE cscart_product_filters SET position = 510 WHERE filter_id = 570 AND company_id = 2; -- Мощность
UPDATE cscart_product_filters SET position = 520 WHERE filter_id = 536 AND company_id = 2; -- Диапазон давления воды
UPDATE cscart_product_filters SET position = 530 WHERE filter_id = 600 AND company_id = 2; -- Система бритья
UPDATE cscart_product_filters SET position = 540 WHERE filter_id = 546 AND company_id = 2; -- Количество режимов работы
UPDATE cscart_product_filters SET position = 550 WHERE filter_id = 609 AND company_id = 2; -- Способ измерения
UPDATE cscart_product_filters SET position = 560 WHERE filter_id = 583 AND company_id = 2; -- Объем
UPDATE cscart_product_filters SET position = 570 WHERE filter_id = 474 AND company_id = 2; -- Объем
UPDATE cscart_product_filters SET position = 580 WHERE filter_id = 2082 AND company_id = 2; -- Мощность
UPDATE cscart_product_filters SET position = 590 WHERE filter_id = 554 AND company_id = 2; -- Максимальная скорость
UPDATE cscart_product_filters SET position = 600 WHERE filter_id = 615 AND company_id = 2; -- Тип уборки
UPDATE cscart_product_filters SET position = 610 WHERE filter_id = 698 AND company_id = 2; -- Совместимые модели
UPDATE cscart_product_filters SET position = 620 WHERE filter_id = 685 AND company_id = 2; -- Объем
UPDATE cscart_product_filters SET position = 630 WHERE filter_id = 602 AND company_id = 2; -- Скорость накачивания
UPDATE cscart_product_filters SET position = 640 WHERE filter_id = 467 AND company_id = 2; -- Мощность
UPDATE cscart_product_filters SET position = 650 WHERE filter_id = 1951 AND company_id = 2; -- Максимальный выходной ток
UPDATE cscart_product_filters SET position = 660 WHERE filter_id = 566 AND company_id = 2; -- Мощность
UPDATE cscart_product_filters SET position = 670 WHERE filter_id = 545 AND company_id = 2; -- Количество режимов работы
UPDATE cscart_product_filters SET position = 680 WHERE filter_id = 549 AND company_id = 2; -- Максимальная мощность
UPDATE cscart_product_filters SET position = 690 WHERE filter_id = 637 AND company_id = 2; -- Дисплей
UPDATE cscart_product_filters SET position = 700 WHERE filter_id = 548 AND company_id = 2; -- Количество функций
UPDATE cscart_product_filters SET position = 710 WHERE filter_id = 544 AND company_id = 2; -- Количество предметов в комплекте
UPDATE cscart_product_filters SET position = 720 WHERE filter_id = 543 AND company_id = 2; -- Количество бит в комплекте
UPDATE cscart_product_filters SET position = 730 WHERE filter_id = 616 AND company_id = 2; -- Тип уборки
UPDATE cscart_product_filters SET position = 740 WHERE filter_id = 654 AND company_id = 2; -- Количество розеток
UPDATE cscart_product_filters SET position = 750 WHERE filter_id = 684 AND company_id = 2; -- Объем
UPDATE cscart_product_filters SET position = 760 WHERE filter_id = 1637 AND company_id = 2; -- Количество предметов в комплекте
UPDATE cscart_product_filters SET position = 770 WHERE filter_id = 579 AND company_id = 2; -- Объем
UPDATE cscart_product_filters SET position = 780 WHERE filter_id = 537 AND company_id = 2; -- Дисплей
UPDATE cscart_product_filters SET position = 790 WHERE filter_id = 624 AND company_id = 2; -- Уровень шума
UPDATE cscart_product_filters SET position = 800 WHERE filter_id = 497 AND company_id = 2; -- Тип кофемашины
UPDATE cscart_product_filters SET position = 810 WHERE filter_id = 686 AND company_id = 2; -- Объем
UPDATE cscart_product_filters SET position = 820 WHERE filter_id = 679 AND company_id = 2; -- Мощность
UPDATE cscart_product_filters SET position = 830 WHERE filter_id = 627 AND company_id = 2; -- Цветовая температура
UPDATE cscart_product_filters SET position = 840 WHERE filter_id = 632 AND company_id = 2; -- Беспроводное подключение
UPDATE cscart_product_filters SET position = 850 WHERE filter_id = 451 AND company_id = 2; -- Максимально поддерживаемая диагональ
UPDATE cscart_product_filters SET position = 860 WHERE filter_id = 1852 AND company_id = 2; -- Максимальная скорость
UPDATE cscart_product_filters SET position = 870 WHERE filter_id = 727 AND company_id = 2; -- Диагональ экрана
UPDATE cscart_product_filters SET position = 880 WHERE filter_id = 525 AND company_id = 2; -- Количество кнопок
UPDATE cscart_product_filters SET position = 890 WHERE filter_id = 571 AND company_id = 2; -- Мощность
UPDATE cscart_product_filters SET position = 900 WHERE filter_id = 683 AND company_id = 2; -- Объем
UPDATE cscart_product_filters SET position = 910 WHERE filter_id = 773 AND company_id = 2; -- Рекомендуемая площадь обслуживания
UPDATE cscart_product_filters SET position = 920 WHERE filter_id = 758 AND company_id = 2; -- Обслуживаемая площадь
UPDATE cscart_product_filters SET position = 930 WHERE filter_id = 498 AND company_id = 2; -- Тип матрицы
UPDATE cscart_product_filters SET position = 940 WHERE filter_id = 1792 AND company_id = 2; -- Совместимые устройства
UPDATE cscart_product_filters SET position = 950 WHERE filter_id = 652 AND company_id = 2; -- Количество насадок в комплекте
UPDATE cscart_product_filters SET position = 960 WHERE filter_id = 833 AND company_id = 2; -- Водонепроницаемость
UPDATE cscart_product_filters SET position = 970 WHERE filter_id = 2095 AND company_id = 2; -- Поддерживаемые платформы
UPDATE cscart_product_filters SET position = 980 WHERE filter_id = 664 AND company_id = 2; -- Максимальная ширина устройства
UPDATE cscart_product_filters SET position = 990 WHERE filter_id = 531 AND company_id = 2; -- Встроенный фонарик
UPDATE cscart_product_filters SET position = 1000 WHERE filter_id = 740 AND company_id = 2; -- Количество насадок в комплекте
UPDATE cscart_product_filters SET position = 1010 WHERE filter_id = 480 AND company_id = 2; -- Разрешение проектора
UPDATE cscart_product_filters SET position = 1020 WHERE filter_id = 559 AND company_id = 2; -- Максимальный ток пуска
UPDATE cscart_product_filters SET position = 1030 WHERE filter_id = 823 AND company_id = 2; -- Тип крепления держателя
UPDATE cscart_product_filters SET position = 1040 WHERE filter_id = 539 AND company_id = 2; -- Интерфейсы
UPDATE cscart_product_filters SET position = 1050 WHERE filter_id = 524 AND company_id = 2; -- Число пикселей матрицы
UPDATE cscart_product_filters SET position = 1060 WHERE filter_id = 447 AND company_id = 2; -- Емкость аккумулятора
UPDATE cscart_product_filters SET position = 1070 WHERE filter_id = 489 AND company_id = 2; -- Совместимость с бутылями
UPDATE cscart_product_filters SET position = 1080 WHERE filter_id = 487 AND company_id = 2; -- Световой поток
UPDATE cscart_product_filters SET position = 1090 WHERE filter_id = 631 AND company_id = 2; -- Беспроводное подключение
UPDATE cscart_product_filters SET position = 1100 WHERE filter_id = 1641 AND company_id = 2; -- Регулировка подсветки
UPDATE cscart_product_filters SET position = 1110 WHERE filter_id = 712 AND company_id = 2; -- Форма линз
UPDATE cscart_product_filters SET position = 1120 WHERE filter_id = 1798 AND company_id = 2; -- Максимальная производительность
UPDATE cscart_product_filters SET position = 1130 WHERE filter_id = 591 AND company_id = 2; -- Разрешение
UPDATE cscart_product_filters SET position = 1140 WHERE filter_id = 552 AND company_id = 2; -- Максимальная нагрузка
UPDATE cscart_product_filters SET position = 1150 WHERE filter_id = 666 AND company_id = 2; -- Максимальное разрешение датчика
UPDATE cscart_product_filters SET position = 1160 WHERE filter_id = 450 AND company_id = 2; -- Количество камер
UPDATE cscart_product_filters SET position = 1170 WHERE filter_id = 593 AND company_id = 2; -- Разъем 2
UPDATE cscart_product_filters SET position = 1180 WHERE filter_id = 671 AND company_id = 2; -- Материал подошвы
UPDATE cscart_product_filters SET position = 1190 WHERE filter_id = 542 AND company_id = 2; -- Количество антенн
UPDATE cscart_product_filters SET position = 1200 WHERE filter_id = 678 AND company_id = 2; -- Мощность
UPDATE cscart_product_filters SET position = 1210 WHERE filter_id = 713 AND company_id = 2; -- Цветовая температура
UPDATE cscart_product_filters SET position = 1220 WHERE filter_id = 653 AND company_id = 2; -- Количество режимов
UPDATE cscart_product_filters SET position = 1230 WHERE filter_id = 563 AND company_id = 2; -- Минимальная длина стрижки
UPDATE cscart_product_filters SET position = 1240 WHERE filter_id = 651 AND company_id = 2; -- Количество насадок
UPDATE cscart_product_filters SET position = 1250 WHERE filter_id = 608 AND company_id = 2; -- Способ бритья
UPDATE cscart_product_filters SET position = 1260 WHERE filter_id = 645 AND company_id = 2; -- Зоны массажа
UPDATE cscart_product_filters SET position = 1270 WHERE filter_id = 2050 AND company_id = 2; -- Беспроводные интерфейсы
UPDATE cscart_product_filters SET position = 1280 WHERE filter_id = 444 AND company_id = 2; -- Диагональ экрана
UPDATE cscart_product_filters SET position = 1290 WHERE filter_id = 681 AND company_id = 2; -- Мощность всасывания
UPDATE cscart_product_filters SET position = 1300 WHERE filter_id = 665 AND company_id = 2; -- Максимальное давление
UPDATE cscart_product_filters SET position = 1310 WHERE filter_id = 646 AND company_id = 2; -- Интенсивность подачи пара
UPDATE cscart_product_filters SET position = 1320 WHERE filter_id = 621 AND company_id = 2; -- Тип питания
UPDATE cscart_product_filters SET position = 1330 WHERE filter_id = 739 AND company_id = 2; -- Количество USB портов
UPDATE cscart_product_filters SET position = 1340 WHERE filter_id = 755 AND company_id = 2; -- Мощность
UPDATE cscart_product_filters SET position = 1350 WHERE filter_id = 677 AND company_id = 2; -- Мощность
UPDATE cscart_product_filters SET position = 1360 WHERE filter_id = 756 AND company_id = 2; -- Мощность
UPDATE cscart_product_filters SET position = 1370 WHERE filter_id = 714 AND company_id = 2; -- Цветовая температура
UPDATE cscart_product_filters SET position = 1380 WHERE filter_id = 533 AND company_id = 2; -- Диагональ
UPDATE cscart_product_filters SET position = 1390 WHERE filter_id = 824 AND company_id = 2; -- Тип матрицы
UPDATE cscart_product_filters SET position = 1400 WHERE filter_id = 745 AND company_id = 2; -- Максимальная нагрузка
UPDATE cscart_product_filters SET position = 1410 WHERE filter_id = 754 AND company_id = 2; -- Мощность
UPDATE cscart_product_filters SET position = 1420 WHERE filter_id = 1900 AND company_id = 2; -- Влагозащищенная поверхность
UPDATE cscart_product_filters SET position = 1430 WHERE filter_id = 844 AND company_id = 2; -- Продолжительность автономной работы
UPDATE cscart_product_filters SET position = 1440 WHERE filter_id = 1848 AND company_id = 2; -- Максимальная мощность
UPDATE cscart_product_filters SET position = 1450 WHERE filter_id = 847 AND company_id = 2; -- Рекомендуемая площадь
UPDATE cscart_product_filters SET position = 1460 WHERE filter_id = 1076 AND company_id = 2; -- Емкость аккумулятора
UPDATE cscart_product_filters SET position = 1470 WHERE filter_id = 454 AND company_id = 2; -- Минимальный ток пуска
UPDATE cscart_product_filters SET position = 1480 WHERE filter_id = 449 AND company_id = 2; -- Зона использования
UPDATE cscart_product_filters SET position = 1490 WHERE filter_id = 746 AND company_id = 2; -- Максимальная нагрузка
UPDATE cscart_product_filters SET position = 1500 WHERE filter_id = 468 AND company_id = 2; -- Мощность
UPDATE cscart_product_filters SET position = 1510 WHERE filter_id = 558 AND company_id = 2; -- Максимальный воздухообмен
UPDATE cscart_product_filters SET position = 1520 WHERE filter_id = 568 AND company_id = 2; -- Мощность
UPDATE cscart_product_filters SET position = 1530 WHERE filter_id = 851 AND company_id = 2; -- Тип крепления устройства
UPDATE cscart_product_filters SET position = 1540 WHERE filter_id = 692 AND company_id = 2; -- Поддержка быстрой зарядки
UPDATE cscart_product_filters SET position = 1550 WHERE filter_id = 589 AND company_id = 2; -- Подсветка
UPDATE cscart_product_filters SET position = 1560 WHERE filter_id = 662 AND company_id = 2; -- Максимальная скорость чтения
UPDATE cscart_product_filters SET position = 1570 WHERE filter_id = 625 AND company_id = 2; -- Установка
UPDATE cscart_product_filters SET position = 1580 WHERE filter_id = 721 AND company_id = 2; -- Время автономной работы
UPDATE cscart_product_filters SET position = 1590 WHERE filter_id = 647 AND company_id = 2; -- Источник питания
UPDATE cscart_product_filters SET position = 1600 WHERE filter_id = 614 AND company_id = 2; -- Тип матрицы
UPDATE cscart_product_filters SET position = 1610 WHERE filter_id = 612 AND company_id = 2; -- Тип матрицы
UPDATE cscart_product_filters SET position = 1620 WHERE filter_id = 667 AND company_id = 2; -- Максимальный пробег на одном заряде
UPDATE cscart_product_filters SET position = 1630 WHERE filter_id = 556 AND company_id = 2; -- Максимальное разрешение видеозаписи
UPDATE cscart_product_filters SET position = 1640 WHERE filter_id = 650 AND company_id = 2; -- Количество каналов акустической системы
UPDATE cscart_product_filters SET position = 1650 WHERE filter_id = 764 AND company_id = 2; -- Объем резервуара для воды
UPDATE cscart_product_filters SET position = 1660 WHERE filter_id = 572 AND company_id = 2; -- Мощность
UPDATE cscart_product_filters SET position = 1670 WHERE filter_id = 786 AND company_id = 2; -- Цвет свечения
UPDATE cscart_product_filters SET position = 1680 WHERE filter_id = 660 AND company_id = 2; -- Максимальная длина стрижки
UPDATE cscart_product_filters SET position = 1690 WHERE filter_id = 735 AND company_id = 2; -- Емкость аккумулятора
UPDATE cscart_product_filters SET position = 1700 WHERE filter_id = 736 AND company_id = 2; -- Емкость пылесборника
UPDATE cscart_product_filters SET position = 1710 WHERE filter_id = 2097 AND company_id = 2; -- Виброотдача
UPDATE cscart_product_filters SET position = 1720 WHERE filter_id = 710 AND company_id = 2; -- Угол обзора по горизонтали
UPDATE cscart_product_filters SET position = 1730 WHERE filter_id = 944 AND company_id = 2; -- Беспроводные подключения
UPDATE cscart_product_filters SET position = 1740 WHERE filter_id = 849 AND company_id = 2; -- Стандарт размеров крепления (VESA)
UPDATE cscart_product_filters SET position = 1750 WHERE filter_id = 700 AND company_id = 2; -- Совместимые модели
UPDATE cscart_product_filters SET position = 1760 WHERE filter_id = 639 AND company_id = 2; -- Длина кабеля
UPDATE cscart_product_filters SET position = 1770 WHERE filter_id = 808 AND company_id = 2; -- Максимальное время непрерывной работы
UPDATE cscart_product_filters SET position = 1780 WHERE filter_id = 634 AND company_id = 2; -- Выходные разъемы на корпусе
UPDATE cscart_product_filters SET position = 1790 WHERE filter_id = 1931 AND company_id = 2; -- Максимальная скорость беспроводного соединения
UPDATE cscart_product_filters SET position = 1800 WHERE filter_id = 866 AND company_id = 2; -- Модель процессора
UPDATE cscart_product_filters SET position = 1810 WHERE filter_id = 610 AND company_id = 2; -- Технология изготовления экрана
UPDATE cscart_product_filters SET position = 1820 WHERE filter_id = 798 AND company_id = 2; -- Длина кабеля
UPDATE cscart_product_filters SET position = 1830 WHERE filter_id = 1935 AND company_id = 2; -- Тип нагревательного элемента
UPDATE cscart_product_filters SET position = 1840 WHERE filter_id = 869 AND company_id = 2; -- Система активного шумоподавления
UPDATE cscart_product_filters SET position = 1850 WHERE filter_id = 1883 AND company_id = 2; -- Модель процессора
UPDATE cscart_product_filters SET position = 1860 WHERE filter_id = 720 AND company_id = 2; -- Быстрая зарядка
UPDATE cscart_product_filters SET position = 1870 WHERE filter_id = 500 AND company_id = 2; -- Тип подключения
UPDATE cscart_product_filters SET position = 1880 WHERE filter_id = 469 AND company_id = 2; -- Мощность
UPDATE cscart_product_filters SET position = 1890 WHERE filter_id = 574 AND company_id = 2; -- Мощность освещения
UPDATE cscart_product_filters SET position = 1900 WHERE filter_id = 656 AND company_id = 2; -- Количество уровней фиксации ручки
UPDATE cscart_product_filters SET position = 1910 WHERE filter_id = 729 AND company_id = 2; -- Диаметр купола
UPDATE cscart_product_filters SET position = 1920 WHERE filter_id = 641 AND company_id = 2; -- Емкость аккумулятора
UPDATE cscart_product_filters SET position = 1930 WHERE filter_id = 744 AND company_id = 2; -- Максимальная длительность видеозаписи
UPDATE cscart_product_filters SET position = 1940 WHERE filter_id = 672 AND company_id = 2; -- Минимальная диагональ экрана
UPDATE cscart_product_filters SET position = 1950 WHERE filter_id = 770 AND company_id = 2; -- Приготовление двух тостов одновременно
UPDATE cscart_product_filters SET position = 1960 WHERE filter_id = 598 AND company_id = 2; -- Световой поток
UPDATE cscart_product_filters SET position = 1970 WHERE filter_id = 636 AND company_id = 2; -- Давление
UPDATE cscart_product_filters SET position = 1980 WHERE filter_id = 1877 AND company_id = 2; -- Технология HDR
UPDATE cscart_product_filters SET position = 1990 WHERE filter_id = 722 AND company_id = 2; -- Время отклика
UPDATE cscart_product_filters SET position = 2000 WHERE filter_id = 709 AND company_id = 2; -- Угол обзора объектива
UPDATE cscart_product_filters SET position = 2010 WHERE filter_id = 529 AND company_id = 2; -- Быстрая зарядка
UPDATE cscart_product_filters SET position = 2020 WHERE filter_id = 768 AND company_id = 2; -- Поддержание температуры
UPDATE cscart_product_filters SET position = 2030 WHERE filter_id = 1485 AND company_id = 2; -- Тип навигации
UPDATE cscart_product_filters SET position = 2040 WHERE filter_id = 725 AND company_id = 2; -- Голосовой помощник
UPDATE cscart_product_filters SET position = 2050 WHERE filter_id = 837 AND company_id = 2; -- Емкость аккумулятора
UPDATE cscart_product_filters SET position = 2060 WHERE filter_id = 1462 AND company_id = 2; -- Количество мегапикселей основной камеры
UPDATE cscart_product_filters SET position = 2070 WHERE filter_id = 1499 AND company_id = 2; -- Ночной режим
UPDATE cscart_product_filters SET position = 2080 WHERE filter_id = 675 AND company_id = 2; -- Мощность
UPDATE cscart_product_filters SET position = 2090 WHERE filter_id = 599 AND company_id = 2; -- Световой поток
UPDATE cscart_product_filters SET position = 2100 WHERE filter_id = 642 AND company_id = 2; -- Емкость резервуара для воды
UPDATE cscart_product_filters SET position = 2110 WHERE filter_id = 945 AND company_id = 2; -- Емкость аккумулятора
UPDATE cscart_product_filters SET position = 2120 WHERE filter_id = 663 AND company_id = 2; -- Максимальная частота обновления
UPDATE cscart_product_filters SET position = 2130 WHERE filter_id = 743 AND company_id = 2; -- Максимальная диагональ экрана
UPDATE cscart_product_filters SET position = 2140 WHERE filter_id = 682 AND company_id = 2; -- Наличие сабвуфера
UPDATE cscart_product_filters SET position = 2150 WHERE filter_id = 760 AND company_id = 2; -- Объем бака для воды
UPDATE cscart_product_filters SET position = 2160 WHERE filter_id = 799 AND company_id = 2; -- Длина кабеля
UPDATE cscart_product_filters SET position = 2170 WHERE filter_id = 737 AND company_id = 2; -- Ионизация
UPDATE cscart_product_filters SET position = 2180 WHERE filter_id = 796 AND company_id = 2; -- Диаметр колес
UPDATE cscart_product_filters SET position = 2190 WHERE filter_id = 441 AND company_id = 2; -- Тип цоколя
UPDATE cscart_product_filters SET position = 2200 WHERE filter_id = 836 AND company_id = 2; -- Емкость аккумулятора
UPDATE cscart_product_filters SET position = 2210 WHERE filter_id = 648 AND company_id = 2; -- Кабель в комплекте
UPDATE cscart_product_filters SET position = 2220 WHERE filter_id = 1858 AND company_id = 2; -- Время работы в активном режиме
UPDATE cscart_product_filters SET position = 2230 WHERE filter_id = 1930 AND company_id = 2; -- Количество LAN портов
UPDATE cscart_product_filters SET position = 2240 WHERE filter_id = 1865 AND company_id = 2; -- Количество HDMI портов
UPDATE cscart_product_filters SET position = 2250 WHERE filter_id = 848 AND company_id = 2; -- Система обнаружения движения
UPDATE cscart_product_filters SET position = 2260 WHERE filter_id = 899 AND company_id = 2; -- Емкость аккумулятора
UPDATE cscart_product_filters SET position = 2270 WHERE filter_id = 1925 AND company_id = 2; -- Емкость аккумулятора
UPDATE cscart_product_filters SET position = 2280 WHERE filter_id = 2093 AND company_id = 2; -- Встроенная память
UPDATE cscart_product_filters SET position = 2290 WHERE filter_id = 2092 AND company_id = 2; -- Оперативная память
UPDATE cscart_product_filters SET position = 2300 WHERE filter_id = 2078 AND company_id = 2; -- Емкость аккумулятора
UPDATE cscart_product_filters SET position = 2310 WHERE filter_id = 734 AND company_id = 2; -- Емкость аккумулятора
UPDATE cscart_product_filters SET position = 2320 WHERE filter_id = 882 AND company_id = 2; -- Емкость аккумулятора
UPDATE cscart_product_filters SET position = 2330 WHERE filter_id = 1937 AND company_id = 2; -- Емкость аккумулятора
UPDATE cscart_product_filters SET position = 2340 WHERE filter_id = 895 AND company_id = 2; -- NFC
UPDATE cscart_product_filters SET position = 2350 WHERE filter_id = 1480 AND company_id = 2; -- Частота обновления экрана
UPDATE cscart_product_filters SET position = 2360 WHERE filter_id = 1044 AND company_id = 2; -- Частота обновления экрана
UPDATE cscart_product_filters SET position = 2370 WHERE filter_id = 860 AND company_id = 2; -- Частота обновления экрана
UPDATE cscart_product_filters SET position = 2380 WHERE filter_id = 482 AND company_id = 2; -- Разрешение экрана
UPDATE cscart_product_filters SET position = 2390 WHERE filter_id = 481 AND company_id = 2; -- Разрешение экрана
UPDATE cscart_product_filters SET position = 2400 WHERE filter_id = 1869 AND company_id = 2; -- Разрешение экрана
UPDATE cscart_product_filters SET position = 2410 WHERE filter_id = 592 AND company_id = 2; -- Разрешение
UPDATE cscart_product_filters SET position = 2420 WHERE filter_id = 472 AND company_id = 2; -- Мощность
UPDATE cscart_product_filters SET position = 2430 WHERE filter_id = 2086 AND company_id = 2; -- Мощность
UPDATE cscart_product_filters SET position = 2440 WHERE filter_id = 676 AND company_id = 2; -- Мощность
UPDATE cscart_product_filters SET position = 2450 WHERE filter_id = 477 AND company_id = 2; -- Объем
UPDATE cscart_product_filters SET position = 2460 WHERE filter_id = 759 AND company_id = 2; -- Объём
UPDATE cscart_product_filters SET position = 2470 WHERE filter_id = 502 AND company_id = 2; -- Тип подключения
UPDATE cscart_product_filters SET position = 2480 WHERE filter_id = 501 AND company_id = 2; -- Тип подключения
UPDATE cscart_product_filters SET position = 2490 WHERE filter_id = 2096 AND company_id = 2; -- Тип подключения
UPDATE cscart_product_filters SET position = 2500 WHERE filter_id = 1913 AND company_id = 2; -- Тип подключения
UPDATE cscart_product_filters SET position = 2510 WHERE filter_id = 605 AND company_id = 2; -- Совместимые модели
UPDATE cscart_product_filters SET position = 2520 WHERE filter_id = 606 AND company_id = 2; -- Совместимые модели
UPDATE cscart_product_filters SET position = 2530 WHERE filter_id = 603 AND company_id = 2; -- Совместимые модели
UPDATE cscart_product_filters SET position = 2540 WHERE filter_id = 879 AND company_id = 2; -- Совместимые модели
UPDATE cscart_product_filters SET position = 2550 WHERE filter_id = 897 AND company_id = 2; -- Поддержка eSIM
UPDATE cscart_product_filters SET position = 2560 WHERE filter_id = 1868 AND company_id = 2; -- Степень влагозащиты IP
UPDATE cscart_product_filters SET position = 2570 WHERE filter_id = 1331 AND company_id = 2; -- Тип манометра
UPDATE cscart_product_filters SET position = 2580 WHERE filter_id = 611 AND company_id = 2; -- Тип
UPDATE cscart_product_filters SET position = 2590 WHERE filter_id = 1921 AND company_id = 2; -- Тип аккумулятора
UPDATE cscart_product_filters SET position = 2600 WHERE filter_id = 527 AND company_id = 2; -- Беспроводное подключение
UPDATE cscart_product_filters SET position = 2610 WHERE filter_id = 448 AND company_id = 2; -- Емкость резервуара
UPDATE cscart_product_filters SET position = 2620 WHERE filter_id = 1856 AND company_id = 2; -- Назначение
UPDATE cscart_product_filters SET position = 2630 WHERE filter_id = 1021 AND company_id = 2; -- Тип
UPDATE cscart_product_filters SET position = 2640 WHERE filter_id = 484 AND company_id = 2; -- Рекомендуемый возраст
UPDATE cscart_product_filters SET position = 2650 WHERE filter_id = 1796 AND company_id = 2; -- Пиковое давление
UPDATE cscart_product_filters SET position = 2660 WHERE filter_id = 1831 AND company_id = 2; -- Возраст
UPDATE cscart_product_filters SET position = 2670 WHERE filter_id = 1850 AND company_id = 2; -- Комплектация
UPDATE cscart_product_filters SET position = 2680 WHERE filter_id = 2081 AND company_id = 2; -- Тип
UPDATE cscart_product_filters SET position = 2690 WHERE filter_id = 797 AND company_id = 2; -- Длина
UPDATE cscart_product_filters SET position = 2700 WHERE filter_id = 445 AND company_id = 2; -- Диагональ экрана (см)
UPDATE cscart_product_filters SET position = 2710 WHERE filter_id = 1332 AND company_id = 2; -- Шкала манометра
UPDATE cscart_product_filters SET position = 2720 WHERE filter_id = 752 AND company_id = 2; -- Место крепления
UPDATE cscart_product_filters SET position = 2730 WHERE filter_id = 1965 AND company_id = 2; -- Тип ремешка
UPDATE cscart_product_filters SET position = 2740 WHERE filter_id = 2025 AND company_id = 2; -- Спортивный дизайн
UPDATE cscart_product_filters SET position = 2750 WHERE filter_id = 493 AND company_id = 2; -- Тип
UPDATE cscart_product_filters SET position = 2760 WHERE filter_id = 473 AND company_id = 2; -- Мощность зарядного устройства
UPDATE cscart_product_filters SET position = 2770 WHERE filter_id = 588 AND company_id = 2; -- Поддержка быстрой зарядки
UPDATE cscart_product_filters SET position = 2780 WHERE filter_id = 530 AND company_id = 2; -- Вид устройства
UPDATE cscart_product_filters SET position = 2790 WHERE filter_id = 523 AND company_id = 2; -- Функции
UPDATE cscart_product_filters SET position = 2800 WHERE filter_id = 494 AND company_id = 2; -- Тип антенн
UPDATE cscart_product_filters SET position = 2810 WHERE filter_id = 499 AND company_id = 2; -- Тип питания
UPDATE cscart_product_filters SET position = 2820 WHERE filter_id = 526 AND company_id = 2; -- RGB подсветка
UPDATE cscart_product_filters SET position = 2830 WHERE filter_id = 538 AND company_id = 2; -- Дисплей
UPDATE cscart_product_filters SET position = 2840 WHERE filter_id = 943 AND company_id = 2; -- Материал лезвий
UPDATE cscart_product_filters SET position = 2850 WHERE filter_id = 547 AND company_id = 2; -- Количество температурных режимов
UPDATE cscart_product_filters SET position = 2860 WHERE filter_id = 443 AND company_id = 2; -- Возрастная группа
UPDATE cscart_product_filters SET position = 2870 WHERE filter_id = 1020 AND company_id = 2; -- Минимальный возраст
UPDATE cscart_product_filters SET position = 2880 WHERE filter_id = 1642 AND company_id = 2; -- Тип экрана
UPDATE cscart_product_filters SET position = 2890 WHERE filter_id = 1643 AND company_id = 2; -- Тематика
UPDATE cscart_product_filters SET position = 2900 WHERE filter_id = 738 AND company_id = 2; -- Источник питания
UPDATE cscart_product_filters SET position = 2910 WHERE filter_id = 2049 AND company_id = 2; -- Разрешение видеосъемки
UPDATE cscart_product_filters SET position = 2920 WHERE filter_id = 1974 AND company_id = 2; -- Разрешение печати
UPDATE cscart_product_filters SET position = 2930 WHERE filter_id = 1071 AND company_id = 2; -- Общее число мегапикселей матрицы
UPDATE cscart_product_filters SET position = 2940 WHERE filter_id = 1040 AND company_id = 2; -- Игровой ноутбук
UPDATE cscart_product_filters SET position = 2950 WHERE filter_id = 1832 AND company_id = 2; -- Размер ноги
UPDATE cscart_product_filters SET position = 2960 WHERE filter_id = 1836 AND company_id = 2; -- Холодопроизводительность
UPDATE cscart_product_filters SET position = 2970 WHERE filter_id = 1851 AND company_id = 2; -- Емкость батареи
UPDATE cscart_product_filters SET position = 2980 WHERE filter_id = 2018 AND company_id = 2; -- Тип газонокосилки
UPDATE cscart_product_filters SET position = 2990 WHERE filter_id = 1132 AND company_id = 2; -- Соотношение сторон
UPDATE cscart_product_filters SET position = 3000 WHERE filter_id = 1966 AND company_id = 2; -- Тип застежки
UPDATE cscart_product_filters SET position = 3010 WHERE filter_id = 790 AND company_id = 2; -- Беспроводное соединение
UPDATE cscart_product_filters SET position = 3020 WHERE filter_id = 555 AND company_id = 2; -- Максимальное разрешение
UPDATE cscart_product_filters SET position = 3030 WHERE filter_id = 701 AND company_id = 2; -- Сопротивление (импеданс)
UPDATE cscart_product_filters SET position = 3040 WHERE filter_id = 607 AND company_id = 2; -- Сопротивление (импеданс)
UPDATE cscart_product_filters SET position = 3050 WHERE filter_id = 576 AND company_id = 2; -- Обслуживаемая площадь
UPDATE cscart_product_filters SET position = 3060 WHERE filter_id = 1846 AND company_id = 2; -- Автооключение
UPDATE cscart_product_filters SET position = 3070 WHERE filter_id = 668 AND company_id = 2; -- Максимальный уровень шума
UPDATE cscart_product_filters SET position = 3080 WHERE filter_id = 693 AND company_id = 2; -- Подключение
UPDATE cscart_product_filters SET position = 3090 WHERE filter_id = 618 AND company_id = 2; -- Тип установки
UPDATE cscart_product_filters SET position = 3100 WHERE filter_id = 705 AND company_id = 2; -- Тип питания
UPDATE cscart_product_filters SET position = 3110 WHERE filter_id = 492 AND company_id = 2; -- Степень защиты
UPDATE cscart_product_filters SET position = 3120 WHERE filter_id = 575 AND company_id = 2; -- Назначение
UPDATE cscart_product_filters SET position = 3130 WHERE filter_id = 587 AND company_id = 2; -- Подача холодного воздуха
UPDATE cscart_product_filters SET position = 3140 WHERE filter_id = 623 AND company_id = 2; -- Угол наклона
UPDATE cscart_product_filters SET position = 3150 WHERE filter_id = 1904 AND company_id = 2; -- Отделение для ноутбука
UPDATE cscart_product_filters SET position = 3160 WHERE filter_id = 595 AND company_id = 2; -- Регулировка руля
UPDATE cscart_product_filters SET position = 3170 WHERE filter_id = 649 AND company_id = 2; -- Количество деталей
UPDATE cscart_product_filters SET position = 3180 WHERE filter_id = 1975 AND company_id = 2; -- Максимальная длина отпечатка
UPDATE cscart_product_filters SET position = 3190 WHERE filter_id = 1072 AND company_id = 2; -- Дисплей
UPDATE cscart_product_filters SET position = 3200 WHERE filter_id = 1799 AND company_id = 2; -- Тип забора воды
UPDATE cscart_product_filters SET position = 3210 WHERE filter_id = 1833 AND company_id = 2; -- Раздвижной ботинок
UPDATE cscart_product_filters SET position = 3220 WHERE filter_id = 1837 AND company_id = 2; -- Тип системы
UPDATE cscart_product_filters SET position = 3230 WHERE filter_id = 2020 AND company_id = 2; -- Ширина скашивания
UPDATE cscart_product_filters SET position = 3240 WHERE filter_id = 2083 AND company_id = 2; -- Максимальное рабочее напряжение
UPDATE cscart_product_filters SET position = 3250 WHERE filter_id = 573 AND company_id = 2; -- Мощность двигателя
UPDATE cscart_product_filters SET position = 3260 WHERE filter_id = 782 AND company_id = 2; -- Тип элементов питания
UPDATE cscart_product_filters SET position = 3270 WHERE filter_id = 1133 AND company_id = 2; -- Контрастность
UPDATE cscart_product_filters SET position = 3280 WHERE filter_id = 863 AND company_id = 2; -- Беспроводная зарядка
UPDATE cscart_product_filters SET position = 3290 WHERE filter_id = 522 AND company_id = 2; -- Форм-фактор дисплея
UPDATE cscart_product_filters SET position = 3300 WHERE filter_id = 1911 AND company_id = 2; -- Максимальная частота кадров
UPDATE cscart_product_filters SET position = 3310 WHERE filter_id = 715 AND company_id = 2; -- Чувствительность
UPDATE cscart_product_filters SET position = 3320 WHERE filter_id = 630 AND company_id = 2; -- Чувствительность
UPDATE cscart_product_filters SET position = 3330 WHERE filter_id = 594 AND company_id = 2; -- Разъемы на блоке зарядного устройства
UPDATE cscart_product_filters SET position = 3340 WHERE filter_id = 1955 AND company_id = 2; -- Ток зарядки
UPDATE cscart_product_filters SET position = 3350 WHERE filter_id = 689 AND company_id = 2; -- Питание
UPDATE cscart_product_filters SET position = 3360 WHERE filter_id = 1845 AND company_id = 2; -- Интенсивность осушения
UPDATE cscart_product_filters SET position = 3370 WHERE filter_id = 695 AND company_id = 2; -- Рекомендуемая площадь обогрева
UPDATE cscart_product_filters SET position = 3380 WHERE filter_id = 696 AND company_id = 2; -- Скорость вращения
UPDATE cscart_product_filters SET position = 3390 WHERE filter_id = 946 AND company_id = 2; -- Емкость резервуара для чистой воды
UPDATE cscart_product_filters SET position = 3400 WHERE filter_id = 702 AND company_id = 2; -- Стандарт Wi-Fi
UPDATE cscart_product_filters SET position = 3410 WHERE filter_id = 733 AND company_id = 2; -- Дисплей
UPDATE cscart_product_filters SET position = 3420 WHERE filter_id = 947 AND company_id = 2; -- Управление
UPDATE cscart_product_filters SET position = 3430 WHERE filter_id = 541 AND company_id = 2; -- Источник питания
UPDATE cscart_product_filters SET position = 3440 WHERE filter_id = 757 AND company_id = 2; -- Насадка для языка
UPDATE cscart_product_filters SET position = 3450 WHERE filter_id = 655 AND company_id = 2; -- Количество скоростей воздушного потока
UPDATE cscart_product_filters SET position = 3460 WHERE filter_id = 690 AND company_id = 2; -- Плавающие головки
UPDATE cscart_product_filters SET position = 3470 WHERE filter_id = 452 AND company_id = 2; -- Механизм
UPDATE cscart_product_filters SET position = 3480 WHERE filter_id = 586 AND company_id = 2; -- Максимальная диагональ ноутбука
UPDATE cscart_product_filters SET position = 3490 WHERE filter_id = 694 AND company_id = 2; -- Регулировка сидения
UPDATE cscart_product_filters SET position = 3500 WHERE filter_id = 1976 AND company_id = 2; -- Максимальная ширина отпечатка
UPDATE cscart_product_filters SET position = 3510 WHERE filter_id = 1073 AND company_id = 2; -- Запись видео
UPDATE cscart_product_filters SET position = 3520 WHERE filter_id = 1800 AND company_id = 2; -- Тип питания
UPDATE cscart_product_filters SET position = 3530 WHERE filter_id = 1838 AND company_id = 2; -- Минимальный уровень шума внутреннего блока
UPDATE cscart_product_filters SET position = 3540 WHERE filter_id = 1853 AND company_id = 2; -- Мощность двигателя
UPDATE cscart_product_filters SET position = 3550 WHERE filter_id = 2021 AND company_id = 2; -- Минимальная высота скашивания
UPDATE cscart_product_filters SET position = 3560 WHERE filter_id = 2084 AND company_id = 2; -- Максимальный рабочий ток
UPDATE cscart_product_filters SET position = 3570 WHERE filter_id = 2094 AND company_id = 2; -- Количество геймпадов в комплекте
UPDATE cscart_product_filters SET position = 3580 WHERE filter_id = 2026 AND company_id = 2; -- Максимальный выходной ток
UPDATE cscart_product_filters SET position = 3590 WHERE filter_id = 1338 AND company_id = 2; -- Тип питания
UPDATE cscart_product_filters SET position = 3600 WHERE filter_id = 1476 AND company_id = 2; -- Сенсорный дисплей
UPDATE cscart_product_filters SET position = 3610 WHERE filter_id = 1927 AND company_id = 2; -- Частота кадров при максимальном разрешении
UPDATE cscart_product_filters SET position = 3620 WHERE filter_id = 730 AND company_id = 2; -- Диаметр мембраны излучателей
UPDATE cscart_product_filters SET position = 3630 WHERE filter_id = 1952 AND company_id = 2; -- Количество USB-A
UPDATE cscart_product_filters SET position = 3640 WHERE filter_id = 1957 AND company_id = 2; -- Материал оплетки
UPDATE cscart_product_filters SET position = 3650 WHERE filter_id = 1891 AND company_id = 2; -- Потребляемая мощность
UPDATE cscart_product_filters SET position = 3660 WHERE filter_id = 1847 AND company_id = 2; -- Объем бака для сбора конденсата
UPDATE cscart_product_filters SET position = 3670 WHERE filter_id = 776 AND company_id = 2; -- С пультом ДУ
UPDATE cscart_product_filters SET position = 3680 WHERE filter_id = 1495 AND company_id = 2; -- Ионизация
UPDATE cscart_product_filters SET position = 3690 WHERE filter_id = 777 AND company_id = 2; -- Система защиты от накипи
UPDATE cscart_product_filters SET position = 3700 WHERE filter_id = 771 AND company_id = 2; -- Расход воды
UPDATE cscart_product_filters SET position = 3710 WHERE filter_id = 827 AND company_id = 2; -- Тип питания
UPDATE cscart_product_filters SET position = 3720 WHERE filter_id = 948 AND company_id = 2; -- Диапазон частот
UPDATE cscart_product_filters SET position = 3730 WHERE filter_id = 780 AND company_id = 2; -- Терморегулятор
UPDATE cscart_product_filters SET position = 3740 WHERE filter_id = 783 AND company_id = 2; -- Управление со смартфона
UPDATE cscart_product_filters SET position = 3750 WHERE filter_id = 774 AND company_id = 2; -- Решетка для подогрева булочек
UPDATE cscart_product_filters SET position = 3760 WHERE filter_id = 789 AND company_id = 2; -- Беспроводное подключение
UPDATE cscart_product_filters SET position = 3770 WHERE filter_id = 717 AND company_id = 2; -- Беспроводное подключение
UPDATE cscart_product_filters SET position = 3780 WHERE filter_id = 949 AND company_id = 2; -- Уровень шума
UPDATE cscart_product_filters SET position = 3790 WHERE filter_id = 765 AND company_id = 2; -- Ортодонтальная насадка
UPDATE cscart_product_filters SET position = 3800 WHERE filter_id = 644 AND company_id = 2; -- Жесткость щетинок
UPDATE cscart_product_filters SET position = 3810 WHERE filter_id = 706 AND company_id = 2; -- Триммер
UPDATE cscart_product_filters SET position = 3820 WHERE filter_id = 728 AND company_id = 2; -- Диаметр колес
UPDATE cscart_product_filters SET position = 3830 WHERE filter_id = 1977 AND company_id = 2; -- Цветность печати
UPDATE cscart_product_filters SET position = 3840 WHERE filter_id = 1074 AND company_id = 2; -- Максимальный объем карты памяти
UPDATE cscart_product_filters SET position = 3850 WHERE filter_id = 1839 AND company_id = 2; -- Класс энергопотребления (охлаждение)
UPDATE cscart_product_filters SET position = 3860 WHERE filter_id = 1854 AND company_id = 2; -- Запас хода
UPDATE cscart_product_filters SET position = 3870 WHERE filter_id = 2022 AND company_id = 2; -- Максимальная высота скашивания
UPDATE cscart_product_filters SET position = 3880 WHERE filter_id = 2085 AND company_id = 2; -- Количество секций
UPDATE cscart_product_filters SET position = 3890 WHERE filter_id = 1922 AND company_id = 2; -- Входные разъемы на корпусе
UPDATE cscart_product_filters SET position = 3900 WHERE filter_id = 1134 AND company_id = 2; -- Количество встроенных динамиков
UPDATE cscart_product_filters SET position = 3910 WHERE filter_id = 785 AND company_id = 2; -- Установка камеры
UPDATE cscart_product_filters SET position = 3920 WHERE filter_id = 2024 AND company_id = 2; -- Поддержка кодеков
UPDATE cscart_product_filters SET position = 3930 WHERE filter_id = 633 AND company_id = 2; -- Воспроизведение через USB Type A
UPDATE cscart_product_filters SET position = 3940 WHERE filter_id = 719 AND company_id = 2; -- Беспроводные интерфейсы
UPDATE cscart_product_filters SET position = 3950 WHERE filter_id = 801 AND company_id = 2; -- Емкость бака для воды/моющего средства
UPDATE cscart_product_filters SET position = 3960 WHERE filter_id = 842 AND company_id = 2; -- Площадь уборки на полном заряде элементов питания
UPDATE cscart_product_filters SET position = 3970 WHERE filter_id = 825 AND company_id = 2; -- Тип питания
UPDATE cscart_product_filters SET position = 3980 WHERE filter_id = 791 AND company_id = 2; -- Вешалка для одежды
UPDATE cscart_product_filters SET position = 3990 WHERE filter_id = 820 AND company_id = 2; -- С дисплеем
UPDATE cscart_product_filters SET position = 4000 WHERE filter_id = 1496 AND company_id = 2; -- Управление со смартфона
UPDATE cscart_product_filters SET position = 4010 WHERE filter_id = 826 AND company_id = 2; -- Тип питания
UPDATE cscart_product_filters SET position = 4020 WHERE filter_id = 838 AND company_id = 2; -- Защита от короткого замыкания
UPDATE cscart_product_filters SET position = 4030 WHERE filter_id = 769 AND company_id = 2; -- Поддержка MESH
UPDATE cscart_product_filters SET position = 4040 WHERE filter_id = 822 AND company_id = 2; -- Срок эксплуатации
UPDATE cscart_product_filters SET position = 4050 WHERE filter_id = 778 AND company_id = 2; -- Способ питания
UPDATE cscart_product_filters SET position = 4060 WHERE filter_id = 803 AND company_id = 2; -- Источник питания
UPDATE cscart_product_filters SET position = 4070 WHERE filter_id = 766 AND company_id = 2; -- Пародонтальная насадка
UPDATE cscart_product_filters SET position = 4080 WHERE filter_id = 810 AND company_id = 2; -- Насадка-диффузор
UPDATE cscart_product_filters SET position = 4090 WHERE filter_id = 864 AND company_id = 2; -- Дисплей
UPDATE cscart_product_filters SET position = 4100 WHERE filter_id = 1901 AND company_id = 2; -- USB разъем
UPDATE cscart_product_filters SET position = 4110 WHERE filter_id = 1978 AND company_id = 2; -- Источник питания
UPDATE cscart_product_filters SET position = 4120 WHERE filter_id = 1075 AND company_id = 2; -- Тип источника питания
UPDATE cscart_product_filters SET position = 4130 WHERE filter_id = 1840 AND company_id = 2; -- Основные режимы
UPDATE cscart_product_filters SET position = 4140 WHERE filter_id = 2023 AND company_id = 2; -- Тип источника питания
UPDATE cscart_product_filters SET position = 4150 WHERE filter_id = 779 AND company_id = 2; -- Степень пылевлагозащиты IP
UPDATE cscart_product_filters SET position = 4160 WHERE filter_id = 1878 AND company_id = 2; -- Соотношение сторон
UPDATE cscart_product_filters SET position = 4170 WHERE filter_id = 1135 AND company_id = 2; -- Суммарная мощность динамиков
UPDATE cscart_product_filters SET position = 4180 WHERE filter_id = 1443 AND company_id = 2; -- Ширина крепления
UPDATE cscart_product_filters SET position = 4190 WHERE filter_id = 1486 AND company_id = 2; -- Тип тормоза
UPDATE cscart_product_filters SET position = 4200 WHERE filter_id = 691 AND company_id = 2; -- Плотность пикселей
UPDATE cscart_product_filters SET position = 4210 WHERE filter_id = 732 AND company_id = 2; -- Дисплей
UPDATE cscart_product_filters SET position = 4220 WHERE filter_id = 781 AND company_id = 2; -- Тип проводного соединения
UPDATE cscart_product_filters SET position = 4230 WHERE filter_id = 1942 AND company_id = 2; -- Линейный AUX вход
UPDATE cscart_product_filters SET position = 4240 WHERE filter_id = 816 AND company_id = 2; -- Проводные интерфейсы
UPDATE cscart_product_filters SET position = 4250 WHERE filter_id = 1953 AND company_id = 2; -- Стандарты быстрой зарядки
UPDATE cscart_product_filters SET position = 4260 WHERE filter_id = 852 AND company_id = 2; -- Типы датчиков и сенсоров
UPDATE cscart_product_filters SET position = 4270 WHERE filter_id = 834 AND company_id = 2; -- Гладильная доска
UPDATE cscart_product_filters SET position = 4280 WHERE filter_id = 865 AND company_id = 2; -- Защита от перегрузки
UPDATE cscart_product_filters SET position = 4290 WHERE filter_id = 1932 AND company_id = 2; -- Температура нагрева воды
UPDATE cscart_product_filters SET position = 4300 WHERE filter_id = 794 AND company_id = 2; -- Гибкая ножка
UPDATE cscart_product_filters SET position = 4310 WHERE filter_id = 741 AND company_id = 2; -- Количество режимов
UPDATE cscart_product_filters SET position = 4320 WHERE filter_id = 821 AND company_id = 2; -- Складная ручка
UPDATE cscart_product_filters SET position = 4330 WHERE filter_id = 1902 AND company_id = 2; -- Светоотражающие элементы
UPDATE cscart_product_filters SET position = 4340 WHERE filter_id = 1045 AND company_id = 2; -- Яркость экрана
UPDATE cscart_product_filters SET position = 4350 WHERE filter_id = 1841 AND company_id = 2; -- Мощность охлаждения
UPDATE cscart_product_filters SET position = 4360 WHERE filter_id = 2079 AND company_id = 2; -- Количество выходных разъемов AC
UPDATE cscart_product_filters SET position = 4370 WHERE filter_id = 1873 AND company_id = 2; -- Производитель процессора
UPDATE cscart_product_filters SET position = 4380 WHERE filter_id = 1880 AND company_id = 2; -- Яркость
UPDATE cscart_product_filters SET position = 4390 WHERE filter_id = 1136 AND company_id = 2; -- Аудиовходы/видеовходы
UPDATE cscart_product_filters SET position = 4400 WHERE filter_id = 1488 AND company_id = 2; -- Складной корпус
UPDATE cscart_product_filters SET position = 4410 WHERE filter_id = 718 AND company_id = 2; -- Беспроводные интерфейсы
UPDATE cscart_product_filters SET position = 4420 WHERE filter_id = 1501 AND company_id = 2; -- Wi-Fi
UPDATE cscart_product_filters SET position = 4430 WHERE filter_id = 1924 AND company_id = 2; -- Диагональ дисплея
UPDATE cscart_product_filters SET position = 4440 WHERE filter_id = 814 AND company_id = 2; -- Поддержка беспроводной зарядки
UPDATE cscart_product_filters SET position = 4450 WHERE filter_id = 1944 AND company_id = 2; -- Разъем для карт памяти
UPDATE cscart_product_filters SET position = 4460 WHERE filter_id = 1910 AND company_id = 2; -- Количество выходных разъемов USB-A
UPDATE cscart_product_filters SET position = 4470 WHERE filter_id = 1893 AND company_id = 2; -- Количество режимов работы
UPDATE cscart_product_filters SET position = 4480 WHERE filter_id = 862 AND company_id = 2; -- Ароматизация
UPDATE cscart_product_filters SET position = 4490 WHERE filter_id = 807 AND company_id = 2; -- Максимальная скорость беспроводного соединения 2.4 ГГц
UPDATE cscart_product_filters SET position = 4500 WHERE filter_id = 1933 AND company_id = 2; -- Автоматическое отключение
UPDATE cscart_product_filters SET position = 4510 WHERE filter_id = 804 AND company_id = 2; -- Количество пульсаций
UPDATE cscart_product_filters SET position = 4520 WHERE filter_id = 1492 AND company_id = 2; -- Зашита от перегрева
UPDATE cscart_product_filters SET position = 4530 WHERE filter_id = 1903 AND company_id = 2; -- Специализированные карманы
UPDATE cscart_product_filters SET position = 4540 WHERE filter_id = 590 AND company_id = 2; -- Производитель процессора
UPDATE cscart_product_filters SET position = 4550 WHERE filter_id = 875 AND company_id = 2; -- Количество ядер
UPDATE cscart_product_filters SET position = 4560 WHERE filter_id = 1874 AND company_id = 2; -- Операционная система
UPDATE cscart_product_filters SET position = 4570 WHERE filter_id = 1879 AND company_id = 2; -- Контрастность
UPDATE cscart_product_filters SET position = 4580 WHERE filter_id = 1137 AND company_id = 2; -- Аудиовыходы/видеовыходы
UPDATE cscart_product_filters SET position = 4590 WHERE filter_id = 1920 AND company_id = 2; -- Встроенный динамик
UPDATE cscart_product_filters SET position = 4600 WHERE filter_id = 787 AND company_id = 2; -- G-сенсор
UPDATE cscart_product_filters SET position = 4610 WHERE filter_id = 843 AND company_id = 2; -- Поддержка быстрой зарядки
UPDATE cscart_product_filters SET position = 4620 WHERE filter_id = 853 AND company_id = 2; -- Форма штекера кабеля
UPDATE cscart_product_filters SET position = 4630 WHERE filter_id = 723 AND company_id = 2; -- Встроенный микрофон
UPDATE cscart_product_filters SET position = 4640 WHERE filter_id = 1954 AND company_id = 2; -- Длина кабеля
UPDATE cscart_product_filters SET position = 4650 WHERE filter_id = 873 AND company_id = 2; -- Станция самоочистки
UPDATE cscart_product_filters SET position = 4660 WHERE filter_id = 881 AND company_id = 2; -- Верхний долив воды
UPDATE cscart_product_filters SET position = 4670 WHERE filter_id = 839 AND company_id = 2; -- Максимальная скорость беспроводного соединения 5 ГГц
UPDATE cscart_product_filters SET position = 4680 WHERE filter_id = 1490 AND company_id = 2; -- Таймер
UPDATE cscart_product_filters SET position = 4690 WHERE filter_id = 658 AND company_id = 2; -- Линейка процессоров
UPDATE cscart_product_filters SET position = 4700 WHERE filter_id = 1834 AND company_id = 2; -- Инвертор
UPDATE cscart_product_filters SET position = 4710 WHERE filter_id = 2080 AND company_id = 2; -- Количество выходных разъемов USB-C
UPDATE cscart_product_filters SET position = 4720 WHERE filter_id = 861 AND company_id = 2; -- Ширина
UPDATE cscart_product_filters SET position = 4730 WHERE filter_id = 1908 AND company_id = 2; -- Выход на порт Type-C
UPDATE cscart_product_filters SET position = 4740 WHERE filter_id = 1864 AND company_id = 2; -- Bluetooth
UPDATE cscart_product_filters SET position = 4750 WHERE filter_id = 802 AND company_id = 2; -- Изогнутый экран
UPDATE cscart_product_filters SET position = 4760 WHERE filter_id = 1138 AND company_id = 2; -- Интерфейсы
UPDATE cscart_product_filters SET position = 4770 WHERE filter_id = 793 AND company_id = 2; -- Встроенный микрофон
UPDATE cscart_product_filters SET position = 4780 WHERE filter_id = 788 AND company_id = 2; -- GPS
UPDATE cscart_product_filters SET position = 4790 WHERE filter_id = 877 AND company_id = 2; -- Приложение для управления
UPDATE cscart_product_filters SET position = 4800 WHERE filter_id = 870 AND company_id = 2; -- Тип питания
UPDATE cscart_product_filters SET position = 4810 WHERE filter_id = 880 AND company_id = 2; -- Ультрафиолетовая стерилизация воды
UPDATE cscart_product_filters SET position = 4820 WHERE filter_id = 1936 AND company_id = 2; -- Шкала уровня воды
UPDATE cscart_product_filters SET position = 4830 WHERE filter_id = 1491 AND company_id = 2; -- Дисплей
UPDATE cscart_product_filters SET position = 4840 WHERE filter_id = 1909 AND company_id = 2; -- Выход на порт USB-A
UPDATE cscart_product_filters SET position = 4850 WHERE filter_id = 819 AND company_id = 2; -- Регулировка по высоте
UPDATE cscart_product_filters SET position = 4860 WHERE filter_id = 1139 AND company_id = 2; -- Wi-Fi
UPDATE cscart_product_filters SET position = 4870 WHERE filter_id = 1479 AND company_id = 2; -- Модуль сотовой связи
UPDATE cscart_product_filters SET position = 4880 WHERE filter_id = 792 AND company_id = 2; -- Время работы в режиме ожидания
UPDATE cscart_product_filters SET position = 4890 WHERE filter_id = 1912 AND company_id = 2; -- Поддержка голосового помощника
UPDATE cscart_product_filters SET position = 4900 WHERE filter_id = 831 AND company_id = 2; -- Wi-Fi
UPDATE cscart_product_filters SET position = 4910 WHERE filter_id = 876 AND company_id = 2; -- Подсветка
UPDATE cscart_product_filters SET position = 4920 WHERE filter_id = 1939 AND company_id = 2; -- Возможность объединения в стереопару (TWS)
UPDATE cscart_product_filters SET position = 4930 WHERE filter_id = 1481 AND company_id = 2; -- Голосовой помощник
UPDATE cscart_product_filters SET position = 4940 WHERE filter_id = 1894 AND company_id = 2; -- Турбощетка в комплекте
UPDATE cscart_product_filters SET position = 4950 WHERE filter_id = 887 AND company_id = 2; -- Регулировка интенсивности испарения
UPDATE cscart_product_filters SET position = 4960 WHERE filter_id = 1929 AND company_id = 2; -- Скорость передачи по проводному подключению
UPDATE cscart_product_filters SET position = 4970 WHERE filter_id = 726 AND company_id = 2; -- Двойные стенки
UPDATE cscart_product_filters SET position = 4980 WHERE filter_id = 1041 AND company_id = 2; -- Количество ядер
UPDATE cscart_product_filters SET position = 4990 WHERE filter_id = 888 AND company_id = 2; -- Количество основных (тыловых) камер
UPDATE cscart_product_filters SET position = 5000 WHERE filter_id = 1866 AND company_id = 2; -- Версия HDMI
UPDATE cscart_product_filters SET position = 5010 WHERE filter_id = 832 AND company_id = 2; -- Видео разъемы
UPDATE cscart_product_filters SET position = 5020 WHERE filter_id = 1478 AND company_id = 2; -- Поддержка SIM-карт
UPDATE cscart_product_filters SET position = 5030 WHERE filter_id = 1493 AND company_id = 2; -- Режим ночной съемки
UPDATE cscart_product_filters SET position = 5040 WHERE filter_id = 1949 AND company_id = 2; -- Дисплей
UPDATE cscart_product_filters SET position = 5050 WHERE filter_id = 716 AND company_id = 2; -- Беспроводная зарядка
UPDATE cscart_product_filters SET position = 5060 WHERE filter_id = 1482 AND company_id = 2; -- Построение карты помещения
UPDATE cscart_product_filters SET position = 5070 WHERE filter_id = 1896 AND company_id = 2; -- Труба всасывания
UPDATE cscart_product_filters SET position = 5080 WHERE filter_id = 1498 AND company_id = 2; -- Управление со смартфона
UPDATE cscart_product_filters SET position = 5090 WHERE filter_id = 1934 AND company_id = 2; -- Дисплей
UPDATE cscart_product_filters SET position = 5100 WHERE filter_id = 1882 AND company_id = 2; -- Тип оперативной памяти
UPDATE cscart_product_filters SET position = 5110 WHERE filter_id = 1887 AND company_id = 2; -- Количество динамиков
UPDATE cscart_product_filters SET position = 5120 WHERE filter_id = 1875 AND company_id = 2; -- Выход на наушники
UPDATE cscart_product_filters SET position = 5130 WHERE filter_id = 1881 AND company_id = 2; -- Выход на наушники
UPDATE cscart_product_filters SET position = 5140 WHERE filter_id = 1914 AND company_id = 2; -- Управление поворотом и наклоном
UPDATE cscart_product_filters SET position = 5150 WHERE filter_id = 1928 AND company_id = 2; -- Режим фотосъемки
UPDATE cscart_product_filters SET position = 5160 WHERE filter_id = 1946 AND company_id = 2; -- Часы
UPDATE cscart_product_filters SET position = 5170 WHERE filter_id = 638 AND company_id = 2; -- Дисплей
UPDATE cscart_product_filters SET position = 5180 WHERE filter_id = 1483 AND company_id = 2; -- Управление со смартфона
UPDATE cscart_product_filters SET position = 5190 WHERE filter_id = 1895 AND company_id = 2; -- Уровень шума
UPDATE cscart_product_filters SET position = 5200 WHERE filter_id = 1497 AND company_id = 2; -- Тип источника питания
UPDATE cscart_product_filters SET position = 5210 WHERE filter_id = 812 AND company_id = 2; -- Отсек для шнура
UPDATE cscart_product_filters SET position = 5220 WHERE filter_id = 892 AND company_id = 2; -- Количество мегапикселей фронтальной камеры
UPDATE cscart_product_filters SET position = 5230 WHERE filter_id = 1876 AND company_id = 2; -- Цифровые тюнеры
UPDATE cscart_product_filters SET position = 5240 WHERE filter_id = 1795 AND company_id = 2; -- Стандарт крепления VESA
UPDATE cscart_product_filters SET position = 5250 WHERE filter_id = 1494 AND company_id = 2; -- Запись скорости
UPDATE cscart_product_filters SET position = 5260 WHERE filter_id = 1943 AND company_id = 2; -- Подсветка корпуса
UPDATE cscart_product_filters SET position = 5270 WHERE filter_id = 1484 AND company_id = 2; -- Пульт ДУ
UPDATE cscart_product_filters SET position = 5280 WHERE filter_id = 815 AND company_id = 2; -- Подсветка
UPDATE cscart_product_filters SET position = 5290 WHERE filter_id = 811 AND company_id = 2; -- Объём накопителя SSD
UPDATE cscart_product_filters SET position = 5300 WHERE filter_id = 894 AND company_id = 2; -- Формат видеосъёмки
UPDATE cscart_product_filters SET position = 5310 WHERE filter_id = 1871 AND company_id = 2; -- Оптическая стабилизация
UPDATE cscart_product_filters SET position = 5320 WHERE filter_id = 806 AND company_id = 2; -- Крепление на стене
UPDATE cscart_product_filters SET position = 5330 WHERE filter_id = 1916 AND company_id = 2; -- ИК подсветка
UPDATE cscart_product_filters SET position = 5340 WHERE filter_id = 1923 AND company_id = 2; -- Разъемы на корпусе
UPDATE cscart_product_filters SET position = 5350 WHERE filter_id = 1945 AND company_id = 2; -- Радио
UPDATE cscart_product_filters SET position = 5360 WHERE filter_id = 829 AND company_id = 2; -- Управление со смартфона
UPDATE cscart_product_filters SET position = 5370 WHERE filter_id = 1046 AND company_id = 2; -- Вид графического ускорителя
UPDATE cscart_product_filters SET position = 5380 WHERE filter_id = 871 AND company_id = 2; -- Системы навигации
UPDATE cscart_product_filters SET position = 5390 WHERE filter_id = 872 AND company_id = 2; -- Слот для карт памяти
UPDATE cscart_product_filters SET position = 5400 WHERE filter_id = 1926 AND company_id = 2; -- Максимальный размер карты памяти
UPDATE cscart_product_filters SET position = 5410 WHERE filter_id = 1940 AND company_id = 2; -- Степень пылевлагозащиты
UPDATE cscart_product_filters SET position = 5420 WHERE filter_id = 1047 AND company_id = 2; -- Модель встроенный видеокарты
UPDATE cscart_product_filters SET position = 5430 WHERE filter_id = 874 AND company_id = 2; -- Датчики
UPDATE cscart_product_filters SET position = 5440 WHERE filter_id = 896 AND company_id = 2; -- Материал ремешка
UPDATE cscart_product_filters SET position = 5450 WHERE filter_id = 1915 AND company_id = 2; -- Поддержка карт памяти
UPDATE cscart_product_filters SET position = 5460 WHERE filter_id = 850 AND company_id = 2; -- Тип крепления
UPDATE cscart_product_filters SET position = 5470 WHERE filter_id = 1948 AND company_id = 2; -- Управление со смартфона
UPDATE cscart_product_filters SET position = 5480 WHERE filter_id = 841 AND company_id = 2; -- Модель дискретной видеокарты
UPDATE cscart_product_filters SET position = 5490 WHERE filter_id = 1855 AND company_id = 2; -- Стандарт связи
UPDATE cscart_product_filters SET position = 5500 WHERE filter_id = 898 AND company_id = 2; -- Вес
UPDATE cscart_product_filters SET position = 5510 WHERE filter_id = 2043 AND company_id = 2; -- Особенности оптики
UPDATE cscart_product_filters SET position = 5520 WHERE filter_id = 883 AND company_id = 2; -- Измерения
UPDATE cscart_product_filters SET position = 5530 WHERE filter_id = 1918 AND company_id = 2; -- Видеоархив
UPDATE cscart_product_filters SET position = 5540 WHERE filter_id = 775 AND company_id = 2; -- Ручка, ремень для переноски
UPDATE cscart_product_filters SET position = 5550 WHERE filter_id = 1892 AND company_id = 2; -- Высота
UPDATE cscart_product_filters SET position = 5560 WHERE filter_id = 867 AND company_id = 2; -- Объём памяти видеокарты
UPDATE cscart_product_filters SET position = 5570 WHERE filter_id = 1022 AND company_id = 2; -- Максимальная мощность зарядки
UPDATE cscart_product_filters SET position = 5580 WHERE filter_id = 1857 AND company_id = 2; -- Прочие функциональные особенности
UPDATE cscart_product_filters SET position = 5590 WHERE filter_id = 1917 AND company_id = 2; -- Степень защиты
UPDATE cscart_product_filters SET position = 5600 WHERE filter_id = 813 AND company_id = 2; -- Питание
UPDATE cscart_product_filters SET position = 5610 WHERE filter_id = 1042 AND company_id = 2; -- Подсветка клавиатуры
UPDATE cscart_product_filters SET position = 5620 WHERE filter_id = 918 AND company_id = 2; -- Цвет ремешка
UPDATE cscart_product_filters SET position = 5630 WHERE filter_id = 900 AND company_id = 2; -- Поддержка беспроводной зарядки
UPDATE cscart_product_filters SET position = 5640 WHERE filter_id = 885 AND company_id = 2; -- Взаимодействие со смартфоном
UPDATE cscart_product_filters SET position = 5650 WHERE filter_id = 1043 AND company_id = 2; -- Цифровой блок клавиатуры
UPDATE cscart_product_filters SET position = 5660 WHERE filter_id = 890 AND company_id = 2; -- Встроенный динамик
UPDATE cscart_product_filters SET position = 5670 WHERE filter_id = 1079 AND company_id = 2; -- Веб-камера
UPDATE cscart_product_filters SET position = 5680 WHERE filter_id = 891 AND company_id = 2; -- Встроенный микрофон
UPDATE cscart_product_filters SET position = 5690 WHERE filter_id = 1049 AND company_id = 2; -- Видеоразъемы
UPDATE cscart_product_filters SET position = 5700 WHERE filter_id = 1048 AND company_id = 2; -- Порт Ethernet
UPDATE cscart_product_filters SET position = 5710 WHERE filter_id = 1884 AND company_id = 2; -- Разъемы USB Type-C
UPDATE cscart_product_filters SET position = 5720 WHERE filter_id = 1867 AND company_id = 2; -- Навигация и определение местоположения
UPDATE cscart_product_filters SET position = 5730 WHERE filter_id = 1475 AND company_id = 2; -- Размер корпуса / ширина крепления
UPDATE cscart_product_filters SET position = 5740 WHERE filter_id = 1889 AND company_id = 2; -- Встроенный кард-ридер
UPDATE cscart_product_filters SET position = 5750 WHERE filter_id = 1886 AND company_id = 2; -- Операционная система
UPDATE cscart_product_filters SET position = 5760 WHERE filter_id = 1870 AND company_id = 2; -- Биометрическая защита
UPDATE cscart_product_filters SET position = 5770 WHERE filter_id = 1050 AND company_id = 2; -- Приблизительное время автономной работы
UPDATE cscart_product_filters SET position = 5780 WHERE filter_id = 1890 AND company_id = 2; -- Толщина
UPDATE cscart_product_filters SET position = 5790 WHERE filter_id = 429 AND company_id = 2; -- Модель
UPDATE cscart_product_filters SET position = 5800 WHERE filter_id = 455 AND company_id = 2; -- Модель
UPDATE cscart_product_filters SET position = 5810 WHERE filter_id = 518 AND company_id = 2; -- Тип устройства
UPDATE cscart_product_filters SET position = 5820 WHERE filter_id = 565 AND company_id = 2; -- Модель
UPDATE cscart_product_filters SET position = 5830 WHERE filter_id = 458 AND company_id = 2; -- Модель
UPDATE cscart_product_filters SET position = 5840 WHERE filter_id = 430 AND company_id = 2; -- Модель
UPDATE cscart_product_filters SET position = 5850 WHERE filter_id = 431 AND company_id = 2; -- Модель
UPDATE cscart_product_filters SET position = 5860 WHERE filter_id = 457 AND company_id = 2; -- Модель
UPDATE cscart_product_filters SET position = 5870 WHERE filter_id = 516 AND company_id = 2; -- Тип устройства
UPDATE cscart_product_filters SET position = 5880 WHERE filter_id = 622 AND company_id = 2; -- Тип устройства
UPDATE cscart_product_filters SET position = 5890 WHERE filter_id = 512 AND company_id = 2; -- Тип устройства
UPDATE cscart_product_filters SET position = 5900 WHERE filter_id = 513 AND company_id = 2; -- Тип устройства
UPDATE cscart_product_filters SET position = 5910 WHERE filter_id = 519 AND company_id = 2; -- Тип устройства
UPDATE cscart_product_filters SET position = 5920 WHERE filter_id = 517 AND company_id = 2; -- Тип устройства
UPDATE cscart_product_filters SET position = 5930 WHERE filter_id = 459 AND company_id = 2; -- Модель
UPDATE cscart_product_filters SET position = 5940 WHERE filter_id = 460 AND company_id = 2; -- Модель
UPDATE cscart_product_filters SET position = 5950 WHERE filter_id = 461 AND company_id = 2; -- Модель
UPDATE cscart_product_filters SET position = 5960 WHERE filter_id = 462 AND company_id = 2; -- Модель
UPDATE cscart_product_filters SET position = 5970 WHERE filter_id = 463 AND company_id = 2; -- Модель
UPDATE cscart_product_filters SET position = 5980 WHERE filter_id = 520 AND company_id = 2; -- Тип устройства
UPDATE cscart_product_filters SET position = 5990 WHERE filter_id = 514 AND company_id = 2; -- Тип устройства
UPDATE cscart_product_filters SET position = 6000 WHERE filter_id = 456 AND company_id = 2; -- Модель
UPDATE cscart_product_filters SET position = 6010 WHERE filter_id = 466 AND company_id = 2; -- Модель устройства
UPDATE cscart_product_filters SET position = 6020 WHERE filter_id = 465 AND company_id = 2; -- Модель устройства
UPDATE cscart_product_filters SET position = 6030 WHERE filter_id = 440 AND company_id = 2; -- Тип устройства
UPDATE cscart_product_filters SET position = 6040 WHERE filter_id = 464 AND company_id = 2; -- Модель
UPDATE cscart_product_filters SET position = 6050 WHERE filter_id = 507 AND company_id = 2; -- Тип устройства
UPDATE cscart_product_filters SET position = 6060 WHERE filter_id = 438 AND company_id = 2; -- Тип устройства
UPDATE cscart_product_filters SET position = 6070 WHERE filter_id = 508 AND company_id = 2; -- Тип устройства
UPDATE cscart_product_filters SET position = 6080 WHERE filter_id = 1989 AND company_id = 2; -- Тип устройства
UPDATE cscart_product_filters SET position = 6090 WHERE filter_id = 511 AND company_id = 2; -- Тип устройства
UPDATE cscart_product_filters SET position = 6100 WHERE filter_id = 509 AND company_id = 2; -- Тип устройства
UPDATE cscart_product_filters SET position = 6110 WHERE filter_id = 1463 AND company_id = 2; -- Модель устройства
UPDATE cscart_product_filters SET position = 6120 WHERE filter_id = 515 AND company_id = 2; -- Тип устройства
UPDATE cscart_product_filters SET position = 6130 WHERE filter_id = 670 AND company_id = 2; -- Материал
UPDATE cscart_product_filters SET position = 6140 WHERE filter_id = 428 AND company_id = 2; -- Модель
UPDATE cscart_product_filters SET position = 6150 WHERE filter_id = 510 AND company_id = 2; -- Тип устройства
UPDATE cscart_product_filters SET position = 6160 WHERE filter_id = 439 AND company_id = 2; -- Тип устройства
UPDATE cscart_product_filters SET position = 6170 WHERE filter_id = 1644 AND company_id = 2; -- Тип устройства
UPDATE cscart_product_filters SET position = 6180 WHERE filter_id = 503 AND company_id = 2; -- Тип устройства
UPDATE cscart_product_filters SET position = 6190 WHERE filter_id = 506 AND company_id = 2; -- Тип устройства
UPDATE cscart_product_filters SET position = 6200 WHERE filter_id = 488 AND company_id = 2; -- Серия
UPDATE cscart_product_filters SET position = 6210 WHERE filter_id = 669 AND company_id = 2; -- Материал
UPDATE cscart_product_filters SET position = 6220 WHERE filter_id = 504 AND company_id = 2; -- Тип устройства
UPDATE cscart_product_filters SET position = 6230 WHERE filter_id = 620 AND company_id = 2; -- Тип устройства
UPDATE cscart_product_filters SET position = 6240 WHERE filter_id = 619 AND company_id = 2; -- Тип устройства
UPDATE cscart_product_filters SET position = 6250 WHERE filter_id = 505 AND company_id = 2; -- Тип устройства
UPDATE cscart_product_filters SET position = 6260 WHERE filter_id = 635 AND company_id = 2; -- Год релиза
UPDATE cscart_product_filters SET position = 6270 WHERE filter_id = 750 AND company_id = 2; -- Материал
UPDATE cscart_product_filters SET position = 6280 WHERE filter_id = 809 AND company_id = 2; -- Материал
UPDATE cscart_product_filters SET position = 6290 WHERE filter_id = 2042 AND company_id = 2; -- Вид
UPDATE cscart_product_filters SET position = 6300 WHERE filter_id = 749 AND company_id = 2; -- Материал
UPDATE cscart_product_filters SET position = 6310 WHERE filter_id = 840 AND company_id = 2; -- Материал
UPDATE cscart_product_filters SET position = 6320 WHERE filter_id = 1898 AND company_id = 2; -- Материал
UPDATE cscart_product_filters SET position = 6330 WHERE filter_id = 1907 AND company_id = 2; -- Материал
UPDATE cscart_product_filters SET position = 6340 WHERE filter_id = 893 AND company_id = 2; -- Материал корпуса
UPDATE cscart_product_filters SET position = 6350 WHERE filter_id = 1941 AND company_id = 2; -- Материал корпуса
UPDATE cscart_product_filters SET position = 6360 WHERE filter_id = 1872 AND company_id = 2; -- Материал корпуса
UPDATE cscart_product_filters SET position = 6370 WHERE filter_id = 1885 AND company_id = 2; -- Материал корпуса
UPDATE cscart_product_filters SET position = 6380 WHERE filter_id = 911 AND company_id = 2; -- Цвет
UPDATE cscart_product_filters SET position = 6390 WHERE filter_id = 914 AND company_id = 2; -- Цвет
UPDATE cscart_product_filters SET position = 6400 WHERE filter_id = 856 AND company_id = 2; -- Цвет
UPDATE cscart_product_filters SET position = 6410 WHERE filter_id = 915 AND company_id = 2; -- Цвет
UPDATE cscart_product_filters SET position = 6420 WHERE filter_id = 909 AND company_id = 2; -- Цвет
UPDATE cscart_product_filters SET position = 6430 WHERE filter_id = 913 AND company_id = 2; -- Цвет
UPDATE cscart_product_filters SET position = 6440 WHERE filter_id = 889 AND company_id = 2; -- Цвет
UPDATE cscart_product_filters SET position = 6450 WHERE filter_id = 859 AND company_id = 2; -- Цвет
UPDATE cscart_product_filters SET position = 6460 WHERE filter_id = 855 AND company_id = 2; -- Цвет
UPDATE cscart_product_filters SET position = 6470 WHERE filter_id = 854 AND company_id = 2; -- Цвет
UPDATE cscart_product_filters SET position = 6480 WHERE filter_id = 2098 AND company_id = 2; -- Цвет
UPDATE cscart_product_filters SET position = 6490 WHERE filter_id = 912 AND company_id = 2; -- Цвет
UPDATE cscart_product_filters SET position = 6500 WHERE filter_id = 907 AND company_id = 2; -- Цвет
UPDATE cscart_product_filters SET position = 6510 WHERE filter_id = 906 AND company_id = 2; -- Цвет
UPDATE cscart_product_filters SET position = 6520 WHERE filter_id = 941 AND company_id = 2; -- Цвет
UPDATE cscart_product_filters SET position = 6530 WHERE filter_id = 857 AND company_id = 2; -- Цвет
UPDATE cscart_product_filters SET position = 6540 WHERE filter_id = 905 AND company_id = 2; -- Цвет
UPDATE cscart_product_filters SET position = 6550 WHERE filter_id = 910 AND company_id = 2; -- Цвет
UPDATE cscart_product_filters SET position = 6560 WHERE filter_id = 940 AND company_id = 2; -- Цвет
UPDATE cscart_product_filters SET position = 6570 WHERE filter_id = 903 AND company_id = 2; -- Цвет
UPDATE cscart_product_filters SET position = 6580 WHERE filter_id = 1906 AND company_id = 2; -- Цвет
UPDATE cscart_product_filters SET position = 6590 WHERE filter_id = 908 AND company_id = 2; -- Цвет
UPDATE cscart_product_filters SET position = 6600 WHERE filter_id = 902 AND company_id = 2; -- Цвет
UPDATE cscart_product_filters SET position = 6610 WHERE filter_id = 1863 AND company_id = 2; -- Цвет
UPDATE cscart_product_filters SET position = 6620 WHERE filter_id = 916 AND company_id = 2; -- Цвет корпуса
UPDATE cscart_product_filters SET position = 6630 WHERE filter_id = 917 AND company_id = 2; -- Цвет
UPDATE cscart_product_filters SET position = 6640 WHERE filter_id = 904 AND company_id = 2; -- Цвет
UPDATE cscart_product_filters SET position = 6650 WHERE filter_id = 901 AND company_id = 2; -- Цвет
