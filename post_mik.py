#! /usr/bin/env python3

import os
import sys
from lxml import etree as ET


class IsCountsCorrect():
    def __init__(self, alias):
        list_of_etrees = IsCountsCorrect.make_etrees_of_Elems_In(alias)
        root_count = IsCountsCorrect.get_root_count_from_etrees(list_of_etrees)
        root_compounds = IsCountsCorrect.name_root_compounds(list_of_etrees)
        simples = root_count - len(root_compounds)
        compounds = 0
        for parent in root_compounds:
            compounds += IsCountsCorrect.count_child_pointers(alias, parent)
            compounds += 1  # we cound parent as 1 item here.

        print('Count Simples xmls:', simples)
        print('Count Compounds xmls', compounds)
        if simples == IsCountsCorrect.count_observed_simples(alias):
            print('simples match')
        else:
            print("BIG DEAL:  Simples Don't Match")
        if compounds == IsCountsCorrect.count_observed_compounds(alias):
            print('compounds match')
        else:
            print("BIG DEAL:  Compounds Don't Match")

    @staticmethod
    def make_etrees_of_Elems_In(alias):
        input_dir = os.path.abspath('Cached_Cdm_files/{}'.format(alias))
        elems_files = ["{}/{}".format(input_dir, i) for i in os.listdir(input_dir) if ('Elems_in_Collection' in i) and ('.xml' in i)]
        return [ET.parse(i) for i in elems_files]

    @staticmethod
    def get_root_count_from_etrees(list_of_etrees):
        set_total_at_root_level = {int(elems_etree.find('./pager/total').text) for elems_etree in list_of_etrees}
        if len(set_total_at_root_level) == 1:
            return set_total_at_root_level.pop()
        else:
            print('BIG DEAL:  either Elems_in_Collection has mismatched number of total counts, or an Elems_in is unreadable')
            return False

    @staticmethod
    def name_root_compounds(list_of_etrees):
        compound_pointers = []
        for i in list_of_etrees:
            for elem in i.findall('.//record/filetype'):
                if elem.text == 'cpd':
                    pointers = {p.text for p in elem.itersiblings(preceding=True) if p.tag == 'pointer'}.union(
                               {p.text for p in elem.itersiblings() if p.tag == 'pointer'})
                    dmrecords = {p.text for p in elem.itersiblings(preceding=True) if p.tag == 'dmrecord'}.union(
                                {p.text for p in elem.itersiblings() if p.tag == 'dmrecord'})
                    if pointers:
                        compound_pointers.append(pointers.pop())
                    elif dmrecords:
                        compound_pointers.append(dmrecords.pop())
        return compound_pointers

    @staticmethod
    def count_child_pointers(alias, cpd_pointer):
        structure_file = os.path.abspath('Cached_Cdm_files/{}/Cpd/{}_cpd.xml'.format(alias, cpd_pointer))
        structure_etree = ET.parse(structure_file)
        child_pointers = [i.text for i in structure_etree.findall('./page/pageptr')]
        return len(child_pointers)

    @staticmethod
    def count_observed_simples(alias):
        for root, dirs, files in os.walk(os.path.abspath('output'.format(alias))):
            if root.split('/')[-1] == '{}_simple'.format(alias):
                output_dir = '{}/original_structure/'.format(root)
                for root, dirs, files in os.walk(output_dir):
                    return len([i for i in files if ".xml" in i])

    @staticmethod
    def count_observed_compounds(alias):
         for root, dirs, files in os.walk(os.path.abspath('output'.format(alias))):
            if root.split('/')[-1] == '{}_compound'.format(alias):
                output_dir = '{}/original_structure/'.format(root)
                compounds_count = 0
                for root, dirs, files in os.walk(output_dir):
                    compounds_count += len([i for i in files if i == "MODS.xml"])
                return compounds_count

if __name__ == '__main__':
    alias = sys.argv[1]
    IsCountsCorrect(alias)
