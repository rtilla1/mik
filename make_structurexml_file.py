#! /usr/bin/env python3

import os
from lxml import etree as ET


for root, dirs, files in os.walk('output'):
    if 'structure.cpd' in files:
        parent = root.split("/")[-1]
        xml_header = '<?xml version="1.0" encoding="utf-8"?>'
        new_etree = ET.Element("islandora_compound_object", title=parent)

        old_etree = ET.parse("{}/structure.cpd".format(root))
        for i in old_etree.findall('.//pageptr'):
            # print('child is: ', i.text)
            new_etree.append(ET.Element('child', content=i.text))

        with open('{}/structure.xml'.format(root), 'wb') as f:
            f.write(ET.tostring(new_etree, encoding="utf-8", xml_declaration=True, pretty_print=True))

print(ET.tostring(new_etree, pretty_print=True, xml_declaration=True, encoding='utf-8'))
