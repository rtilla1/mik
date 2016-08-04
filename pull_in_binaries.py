#! /usr/bin/env python3

import os
import sys
from shutil import copyfile

'''usage is:
    from base directory of mik,
    after mods successfully created,
    (for either simple or compound or both),
    shellcommand:
                  'python3 pull_in_binaries.py p16313coll13'

   result is:
    for each mods item in the output/alias_simpleorcompound/original_structure/ directory,
    the matching binary will be pulled from mik/Cached_Cdm_files/alias/

   a tiny error message will print on the shell screen if no matching file is found
'''


def makedict_sourcefiles(alias):
    sourcefiles_paths = dict()
    input_dir = 'Cached_Cdm_files/{}'.format(alias)
    for root, dirs, files in os.walk(input_dir):
        for file in files:
            if file.split('.')[-1] in ('jp2', 'mp4', 'mp3', 'pdf'):
                alias = file.split('.')[0]
                sourcefiles_paths[alias] = (root, file)
    return sourcefiles_paths


def makelist_simpleoutfolderxmls(alias):
    xml_filelist = []
    subfolder = "output/{}_simple/original_structure".format(alias)
    for root, dirs, files in os.walk(subfolder):
        for file in files:
            if '.xml' in file:
                pointer = file.split('.')[0]
                xml_filelist.append(('simple', root, pointer))
    return xml_filelist


def makelist_compoundoutfolderxmls(alias):
    xml_filelist = []
    subfolder = "output/{}_compound/original_structure".format(alias)
    for root, dirs, files in os.walk(subfolder):
        for file in files:
            if file == 'MODS.xml':
                pointer = root.split('/')[-1]
                xml_filelist.append(('compound', root, pointer))
    return xml_filelist


def is_binary_in_output_dir(kind, root, pointer):
    acceptable_binary_types = ('mp3', 'mp4', 'jp2', 'pdf')
    if kind == 'simple':
        for filetype in acceptable_binary_types:
            if os.path.isfile('{}/{}.{}'.format(root, pointer, filetype)):
                return True
    if kind == 'compound':
        for filetype in acceptable_binary_types:
            if os.path.isfile('{}/{}/OBJ.{}'.format(root, pointer, filetype)):
                return True
    return False


def copy_binary(kind, sourcepath, sourcefile, outroot, pointer):

    if kind == 'simple':
        copyfile("{}/{}".format(sourcepath, sourcefile), "{}/{}".format(outroot, sourcefile))
    elif kind == 'compound':
        copyfile("{}/{}".format(sourcepath, sourcefile), "{}/OBJ.{}".format(outroot, sourcefile.split('.')[-1]))


if __name__ == '__main__':
    alias = sys.argv[1]
    sourcefiles_paths = makedict_sourcefiles(alias)
    simplexmls_list = makelist_simpleoutfolderxmls(alias)
    compoundxmls_list = makelist_compoundoutfolderxmls(alias)
    for filelist in (simplexmls_list, compoundxmls_list):
        for kind, outroot, pointer in filelist:
            if pointer not in sourcefiles_paths:
                if ("_compound" in outroot) and (outroot.split('/')[-2] == "original_structure"):
                    continue  # root of cpd is expected to have no binary
                else:
                    print("!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!")
                    print("{}.xml may not have a matching binary".format(pointer))
                    print("!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!")
            if is_binary_in_output_dir(kind, outroot, pointer):
                continue
            if pointer not in sourcefiles_paths:
                continue
            sourcepath, sourcefile = sourcefiles_paths[pointer]
            copy_binary(kind, sourcepath, sourcefile, outroot, pointer)
