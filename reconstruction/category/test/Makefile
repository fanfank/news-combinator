all:
	g++ -c -O3 -DLOGGER_LEVEL=LL_WARN -Wall -g -I../src cppjieba_simple.cpp
	g++ -o cppjieba_simple cppjieba_simple.o
gpp:
	g++ -o cppjieba_simple -O3 -DLOGGER_LEVEL=LL_WARN -Wall -g -I../src cppjieba_simple.cpp
gcc:
	gcc -o cppjieba_simple_gcc -O3 -DLOGGER_LEVEL=LL_WARN -Wall -g -I../src cppjieba_simple.cpp
clean:
	rm -f *.o *.a cppjieba_simple cppjieba_simple_gcc cppjieba_db
