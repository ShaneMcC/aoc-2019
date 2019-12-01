#!/bin/bash

IMAGE=shanemcc/aoc-2019-01
DOCKERFILE="Dockerfile"

if [ "${1}" = "--jit" ]; then
	IMAGE="${IMAGE}-jit"
	DOCKERFILE="${DOCKERFILE}-jit"
	shift;
elif [ "${1}" = "--hhvm" ]; then
	IMAGE="${IMAGE}-hhvm"
	DOCKERFILE="${DOCKERFILE}-hhvm"
	shift;
fi;

docker image inspect $IMAGE >/dev/null 2>&1
if [ $? -ne 0 ]
then
    echo "One time setup: building docker image ${IMAGE}..."
    cd docker
    docker build . -t $IMAGE --file ${DOCKERFILE}
    cd ..
fi

docker run --rm -it -v $(pwd):/code $IMAGE /entrypoint.sh $@