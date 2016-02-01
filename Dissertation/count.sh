#!/bin/bash

cd sections
echo -e "\nTotal Words in Report:"
texcount introduction.tex motivation.tex background.tex specification.tex design.tex implementation.tex external.tex evaluation.tex furtherwork.tex summary.tex -brief

