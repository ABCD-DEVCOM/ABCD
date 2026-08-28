FROM java:7-jdk

ENV JETTY_HOME=/opt/ABCD/empweb/jetty
ENV JETTY_RUN=/opt/ABCD/empweb
ENV JAVA=/usr/bin/java

COPY ./www/cgi-bin_Linux /opt/ABCD/www/cgi-bin
COPY ./empweb /opt/ABCD/empweb
#COPY ./docker/empweb/mysql-connector-java-3.1.12-bin.jar /opt/ABCD/empweb/common/ext/mysql-connector-java-bin.jar
#COPY ./docker/empweb/mariadb-java-client-1.8.0.jar /opt/ABCD/empweb/common/ext/mysql-connector-java-bin.jar
#COPY ./docker/empweb/mariadb-java-client-2.7.3.jar /opt/ABCD/empweb/common/ext/mysql-connector-java-bin.jar
COPY ./docker/empweb/mysql-connector-java-5.1.49-bin.jar /opt/ABCD/empweb/common/ext/mysql-connector-java-bin.jar

WORKDIR /opt/ABCD

ENTRYPOINT ["/usr/bin/java"]
CMD ["-server","-DSTART=/opt/ABCD/empweb/common/etc/start.config","-Djetty.home=/opt/ABCD/empweb/jetty","-Dempweb.home=/opt/ABCD/empweb","-Djava.util.logging.config.file=/opt/ABCD/empweb/common/etc/logging.properties","-Daxis.xml.reuseParsers=true","-Dsun.net.client.defaultConnectTimeout=10000","-Dcisis.location=/opt/ABCD/www/cgi-bin/ansi/","-Dcisis.command=mx","-Dabcd.url=http://abcd","-Dcisis.platform=linux","-Xms128M","-Xmx128M","-Djetty.home=/opt/ABCD/empweb/jetty","-Djava.library.path=/opt/ABCD/empweb/common/ext","-Dfile.encoding=ISO8859_1","-jar","/opt/ABCD/empweb/jetty/start.jar","/opt/ABCD/empweb/common/etc/ewdbws-jetty.xml","/opt/ABCD/empweb/common/etc/ewengine-jetty.xml","/opt/ABCD/empweb/common/etc/ewgui-jetty.xml"]
