#!/bin/bash

cd sections
echo -e "\nTotal Words in Report:"
texcount introduction.tex motivation.tex background.tex specification.tex design.tex implementation.tex testing.tex evaluation.tex external.tex furtherwork.tex summary.tex -brief

